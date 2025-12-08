<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QZTrayController extends Controller
{
    /**
     * Sign data for QZ Tray using RSA private key from file
     * This endpoint is called by QZ Tray to verify the certificate
     */
    public function signQzRequest(Request $request)
    {
        $requestData = $request->input('data');

        // Path to private key file: storage/app/keys/private-key.pem
        $absolutePath = storage_path('app/keys/private-key.pem');

        // 1. File existence check using 'file_exists'
        if (!file_exists($absolutePath)) {
            return response()->json([
                'error' => 'PHP file_exists() check failed. Key not found.',
                'checked_path' => $absolutePath
            ], 500);
        }

        // 2. Read private key using 'file_get_contents'
        $privateKey = file_get_contents($absolutePath);

        // Check if file_get_contents returned false
        if ($privateKey === false) {
            return response()->json([
                'error' => 'PHP file_get_contents() failed. Key found but UNREADABLE.',
                'checked_path' => $absolutePath
            ], 500);
        }

        $signature = null;

        // 3. Attempt to sign the data
        if (!openssl_sign($requestData, $signature, $privateKey, 'sha1')) {
            return response()->json(['error' => 'Failed to sign data. Check OpenSSL.'], 500);
        }

        if ($signature) {
            return response()->json(['signature' => base64_encode($signature)]);
        }

        return response()->json(['error' => 'Signing failed'], 500);
    }
}
