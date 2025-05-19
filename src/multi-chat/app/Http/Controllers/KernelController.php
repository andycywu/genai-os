<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Http;

class KernelController extends Controller
{
    public function fetchData()
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/read';

        try {
            $response = Http::timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not reach the API. Please try again later.'], 500);
        }
    }

    public function updateField(Request $request)
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/update';
        $data = [
            'access_code' => $request->input('access_code'),
            'url' => $request->input('url'),
            $request->input('field') => $request->input('value'),
        ];

        $response = Http::post($apiUrl, $data);

        return $response->successful() ? response()->json(['status' => 'success']) : response()->json(['error' => 'Failed to update data'], 500);
    }

    public function updateData(Request $request)
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/update';
        $response = Http::post($apiUrl, $request->all());
        return $response->successful() ? response()->json(['status' => 'success']) : response()->json(['error' => 'Failed to update data'], 500);
    }

    public function deleteData(Request $request)
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/delete';
        $response = Http::post($apiUrl, $request->all());
        return $response->successful() ? response()->json(['status' => 'success']) : response()->json(['error' => 'Failed to delete data'], 500);
    }

    public function shutdown(Request $request)
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/shutdown';
        $response = Http::post($apiUrl, $request->all());
        return $response->successful() ? response()->json(['status' => 'success']) : response()->json(['error' => 'Failed to shutdown address'], 500);
    }

    // New method to create data
    public function createData(Request $request)
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/worker/create'; // Ensure this endpoint supports creating
        $data = [
            'access_code' => $request->input('access_code'),
            'url' => $request->input('url'),
            'status' => $request->input('status'),
            'history_id' => $request->input('history_id'),
            'user_id' => $request->input('user_id'),
        ];

        $response = Http::post($apiUrl, $data);

        return $response->successful() ? response()->json(['status' => 'success']) : response()->json(['error' => 'Failed to create data'], 500);
    }
    public function storage()
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/model/';

        try {
            $response = Http::timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not reach the API. Please try again later.'], 500);
        }
    }

    public function storage_job()
    {
        $apiUrl = SystemSetting::where('key', 'kernel_location')->first()->value . '/v1.0/model/jobs';

        try {
            $response = Http::timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not reach the API. Please try again later.'], 500);
        }
    }

    public function storage_download(Request $request)
    {
        $modelName = $request->input('model_name');
        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/download?model_name={$modelName}";

        // Create a new StreamedResponse
        $response = new StreamedResponse(function () use ($apiUrl) {
            // Use Guzzle or any HTTP client to fetch the streaming response
            $client = new \GuzzleHttp\Client();

            // Make a streaming request to the external API
            $apiResponse = $client->get($apiUrl, [
                'stream' => true, // Enable streaming
            ]);

            // Check if the response is successful
            if ($apiResponse->getStatusCode() === 200) {
                $body = $apiResponse->getBody();

                // Stream the response body directly
                while (!$body->eof()) {
                    // Read and output chunks of data
                    echo $body->read(1024); // Read 1 KB at a time
                    flush(); // Send output immediately
                }
            } else {
                // Handle errors accordingly
                echo 'Error: ' . $apiResponse->getStatusCode() . "\n";
                flush();
            }
        });

        // Set headers for text streaming
        $response->headers->set('Content-Type', 'text/plain'); // Set to plain text
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable buffering for Nginx
        $response->headers->set('charset', 'utf-8');
        $response->headers->set('Connection', 'close'); // Close connection after response

        return $response; // Return the streamed response
    }

    public function storage_abort(Request $request)
    {
        $modelName = $request->input('model_name');
        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/abort";

        $response = Http::post($apiUrl, [
            'model_name' => $modelName,
        ]);

        return $response->successful() ? response()->json($response->json()) : response()->json(['error' => 'Failed to fetch data'], 500);
    }
    public function storage_hf_login(Request $request)
    {
        $token = $request->input('token');

        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/hf_login";

        try {
            $response = !$token ? Http::timeout(5)->get($apiUrl) : Http::timeout(5)->post($apiUrl, ['token' => $token]);

            return $response->successful() ? response()->json($response->json()) : response()->json(['error' => 'Failed to fetch data'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not reach the API. Please try again later.'], 500);
        }
    }

    public function storage_remove(Request $request)
    {
        $folder_name = $request->input('folder_name');

        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/remove";

        $response = Http::post($apiUrl, [
            'folder_name' => $folder_name,
        ]);
        return response()->json($response->json());
    }
    public function storage_start(Request $request)
    {
        $model_path = $request->input('model_path');

        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/start";

        $response = Http::post($apiUrl, [
            'model_path' => $model_path,
        ]);
        return response()->json($response->json());
    }

    public function storage_hf_logout(Request $request)
    {
        $baseUrl = SystemSetting::where('key', 'kernel_location')->first()->value;
        $apiUrl = "{$baseUrl}/v1.0/model/hf_logout";

        $response = Http::post($apiUrl);

        return $response->successful() ? response()->json($response->json()) : response()->json(['error' => 'Failed to fetch data'], 500);
    }
}
