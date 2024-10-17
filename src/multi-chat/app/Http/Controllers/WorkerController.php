<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class WorkerController extends Controller
{
    // Route handler for starting workers
    public function start(Request $request)
    {
        $count = $request->validate(['count' => 'required|integer|min:1'])['count'];
        return $this->startWorkers($count);
    }

    // Route handler for stopping workers
    public function stop()
    {
        return $this->stopWorkers();
    }

    // Function to start a specified number of workers
    public function startWorkers(int $count)
    {
        $artisanPath = base_path('artisan');
        $logFileBase = base_path('storage/logs/worker.log');

        for ($i = 0; $i < $count; $i++) {
            $logFile = $this->generateLogFileName($logFileBase);
            $command = PHP_OS_FAMILY === 'Windows' 
                ? "start /B php {$artisanPath} queue:work >> {$logFile} 2>&1" 
                : "php {$artisanPath} queue:work >> {$logFile} 2>&1 &";

            try {
                Process::fromShellCommandline($command)->start();
                // Wait until the log file is created as an indication that the worker started
                while (!file_exists($logFile)) {
                    usleep(100000);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Worker failed to start: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Workers started successfully.']);
    }

    // Function to stop all workers and merge log files
    public function stopWorkers()
    {
        // Signal all queue workers to restart, effectively stopping them
        Artisan::call('queue:restart');

        // Wait up to 5 seconds for all workers to terminate
        for ($elapsedTime = 0; $elapsedTime < 10 && $this->get()->getData()->worker_count > 0; $elapsedTime++) {
            usleep(500000);
        }

        // Merge worker logs into a single file
        $logDirectory = base_path('storage/logs/');
        $mergedLogFile = $logDirectory . 'workers.log';

        try {
            $logFiles = glob($logDirectory . 'worker.log.*');

            if (empty($logFiles)) {
                return response()->json(['message' => 'No workers opened.']);
            }

            // Open merged log file for appending, create if not exists
            $mergedFileHandle = fopen($mergedLogFile, 'a') ?: touch($mergedLogFile);

            foreach ($logFiles as $logFile) {
                if (filesize($logFile) > 0) {
                    fwrite($mergedFileHandle, file_get_contents($logFile));
                }
                unlink($logFile); // Remove the individual worker log file after merging
            }

            fclose($mergedFileHandle);
        } catch (\Exception $e) {
            \Log::error('Log merge error: ' . $e->getMessage());
        }

        return response()->json(['message' => 'All workers stopped and logs merged.']);
    }

    // Function to generate a unique log file name
    private function generateLogFileName($baseName)
    {
        $i = 0;
        while (file_exists($baseName . '.' . $i)) {
            $i++;
        }
        return $baseName . '.' . $i;
    }

    // Get the number of active worker processes
    public function get()
    {
        // Define the project root directory.
        $projectRoot = base_path();
        $artisanFile = $projectRoot . '/artisan';

        if (PHP_OS_FAMILY === 'Windows') {
            // Use tasklist to get the processes.
            $cmd = 'tasklist /FI "IMAGENAME eq php.exe" /FO CSV';
            $processes = shell_exec($cmd);

            // Subtract 2 to account for headers or irrelevant processes.
            $count = max(0, count(explode("\n", $processes)) - 2);
        } else {
            // Use lsof to find PHP processes accessing the artisan file.
            $cmd = "lsof -t '$artisanFile' | xargs ps -p | grep 'php' | grep -v grep";
            $processes = shell_exec($cmd);

            // Count the number of lines in the output.
            $count = count(array_filter(explode("\n", trim($processes))));
        }

        return response()->json(['worker_count' => $count]);
    }

    // Clean Redis keys (no changes here)
    public static function cleanRedisKey($key, $pattern)
    {
        return strpos($key, $pattern) !== false ? substr($key, strpos($key, $pattern)) : $key;
    }
}
