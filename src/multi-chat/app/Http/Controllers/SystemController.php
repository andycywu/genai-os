<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\RedisController;
use App\Http\Controllers\WorkerController;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Process\Process;

class SystemController extends Controller
{
    public static function updateSystemSetting($key, $value)
    {
        SystemSetting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
    }

    public function checkUpdate(Request $request){
        return SystemSetting::checkUpdate(true);
    }

    public function update(Request $request): RedirectResponse
    {
        $extractBaseUrl = fn($url) => $url ? parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . (parse_url($url, PHP_URL_PORT) ? ':' . parse_url($url, PHP_URL_PORT) : '') : '';

        if ($request->input('tab') === 'kernel') {
            foreach (
                [
                    'kernel_location' => $extractBaseUrl($request->input('kernel_location') ?? 'http://localhost:9000'),
                    'safety_guard_location' => $extractBaseUrl($request->input('safety_guard_location')),
                ]
                as $key => $location
            ) {
                $this->updateSystemSetting($key, $location);
            }
            $result = 'success';
        } elseif ($request->input('tab') === 'settings') {
            $smtpConfigured = !in_array(null, [config('app.MAIL_MAILER'), config('app.MAIL_HOST'), config('app.MAIL_PORT'), config('app.MAIL_USERNAME'), config('app.MAIL_PASSWORD'), config('app.MAIL_ENCRYPTION'), config('app.MAIL_FROM_ADDRESS'), config('app.MAIL_FROM_NAME')]);

            foreach (['allow_register', 'register_need_invite'] as $key) {
                $this->updateSystemSetting($key, $request->input($key) === 'allow' && $smtpConfigured ? 'true' : 'false');
            }

            $result = $smtpConfigured ? 'success' : 'smtp_not_configured';

            // Update announcement
            $announcement = $request->input('announcement');
            $currentAnnouncement = SystemSetting::where('key', 'announcement')->value('value');
            if ($currentAnnouncement !== $announcement) {
                User::query()->update(['announced' => false]);
            }

            foreach (['upload_max_size_mb', 'upload_max_file_count'] as $key) {
                $value = ((string) intval($request->input($key))) ?? '10';
                if ($value === '0' && $request->input($key) !== '0') {
                    $value = '10';
                }
                $this->updateSystemSetting($key, $value);
            }

            $tos = $request->input('tos');
            $currentTos = SystemSetting::where('key', 'tos')->value('value');
            if ($currentTos !== $tos) {
                User::query()->update(['term_accepted' => false]);
            }
            $uploadExtensions = $request->input('upload_allowed_extensions') ? implode(',', array_map('trim', explode(',', $request->input('upload_allowed_extensions')))) : 'pdf,doc,docx,odt,ppt,pptx,odp,xlsx,xls,ods,eml,txt,md,csv,json,jpeg,jpg,gif,png,avif,webp,bmp,ico,cur,tiff,tif,zip,mp3,wav,mp4';
            $this->updateSystemSetting('upload_allowed_extensions', $uploadExtensions);
            $this->updateSystemSetting('updateweb_git_ssh_command', $request->input('updateweb_git_ssh_command'));
            $this->updateSystemSetting('updateweb_path', $request->input('updateweb_path'));
            $this->updateSystemSetting('warning_footer', $request->input('warning_footer'));
            $this->updateSystemSetting('announcement', $announcement);
            $this->updateSystemSetting('tos', $tos);
        }

        return Redirect::route('manage.home')->with(['last_tab' => $request->input('tab'), 'last_action' => 'update', 'status' => $result]);
    }

    public function ResetRedis(Request $request)
    {
        foreach (['usertask_', 'api_'] as $prefix) {
            foreach (Redis::keys("{$prefix}*") as $key) {
                $cleanKey = RedisController::cleanRedisKey($key, $prefix);
                Redis::del($cleanKey);
            }
        }

        return Redirect::route('manage.home')->with('last_tab', 'settings')->with('last_action', 'resetRedis')->with('status', 'success');
    }

    public function updateWeb(Request $request)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        try {
            $projectRoot = base_path();
            $scriptPath = stripos(PHP_OS, 'WIN') === 0 ? '/executables/bat/production_update.bat' : '/executables/sh/production_update.sh';
            chdir($projectRoot . dirname($scriptPath));

            echo 'data: ' . json_encode(['status' => 'success', 'output' => 'Current dir: ' . getcwd()]) . "\n\n";
            ob_flush();
            flush();

            foreach (['git stash', 'git pull'] as $command) {
                $this->runCommand($command, $projectRoot);
            }

            if (stripos(PHP_OS, 'WIN') === false) {
                $this->makeExecutable(basename($scriptPath));
            }
            $this->runCommand((stripos(PHP_OS, 'WIN') === 0 ? '' : './') . basename($scriptPath), $projectRoot);
            $workerController = new WorkerController();
            $workerController->stopWorkers();
            $workerController->startWorkers();
            echo 'data: ' . json_encode(['status' => 'success', 'output' => 'Update completed successfully!']) . "\n\n";
            ob_flush();
            flush();
        } catch (\Exception $e) {
            echo 'data: ' . json_encode(['status' => 'error', 'output' => $e->getMessage()]) . "\n\n";
            ob_flush();
            flush();
        }
    }

    private function runCommand(string $command, string $projectRoot)
    {
        $gitSshCommand = SystemSetting::where('key', 'updateweb_git_ssh_command')->first()->value ?? '';

        $customPath = SystemSetting::where('key', 'updateweb_path')->value('value');

        $defaultPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? getenv('PATH') : getenv('PATH');

        $env = [
            'PATH' => !empty($customPath) ? $customPath : $defaultPath,
        ];

        if (!empty($gitSshCommand)) {
            $env['GIT_SSH_COMMAND'] = $gitSshCommand;
        }

        $process = Process::fromShellCommandline($command);
        $process->setEnv($env);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) use ($projectRoot) {
            $this->handleOutput($buffer, $projectRoot);
        });

        if (!$process->isSuccessful()) {
            echo 'data: ' . json_encode(['status' => 'error', 'output' => "Error executing command: $command"]) . "\n\n";
            ob_flush();
            flush();
            exit();
        }
    }

    private function handleOutput(string $buffer, string $projectRoot)
    {
        $encoding = mb_detect_encoding($buffer, ['UTF-8', 'BIG5', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding !== false && $encoding !== 'UTF-8') {
            $buffer = mb_convert_encoding($buffer, 'UTF-8', $encoding);
        }

        if (strpos($buffer, 'password') !== false) {
            $this->sendError('Password prompt detected. Cancelling job...');
        } elseif (strpos($buffer, 'dubious ownership') !== false) {
            $this->sendError("Dubious ownership detected. Please run: git config --global --add safe.directory {$projectRoot}");
        } else {
            echo 'data: ' . json_encode(['status' => 'progress', 'output' => trim($buffer)]) . "\n\n";
            ob_flush();
            flush();
        }
    }

    private function makeExecutable(string $scriptName)
    {
        $process = Process::fromShellCommandline("chmod +x $scriptName");
        $process->run(function ($type, $buffer) {
            echo 'data: ' . json_encode(['status' => 'progress', 'output' => trim($buffer)]) . "\n\n";
            ob_flush();
            flush();
        });

        if (!$process->isSuccessful()) {
            echo 'data: ' . json_encode(['status' => 'error', 'output' => 'Error making the script executable.']) . "\n\n";
            ob_flush();
            flush();
            exit();
        }
    }

    private function sendError(string $message)
    {
        echo 'data: ' . json_encode(['status' => 'error', 'output' => $message]) . "\n\n";
        ob_flush();
        flush();
        exit();
    }
}
