<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Download</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Secure File Download</h1>
                <p class="text-gray-600 mt-2">{{ $secureFile->name }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>File Size:</span>
                    <span class="font-medium">{{ $secureFile->getFormattedFileSize() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-600 mt-2">
                    <span>Downloads:</span>
                    <span class="font-medium">{{ $secureFile->download_count }}/{{ $secureFile->download_limit }}</span>
                </div>
                @if($secureFile->expires_at)
                <div class="flex items-center justify-between text-sm text-gray-600 mt-2">
                    <span>Expires:</span>
                    <span class="font-medium">{{ $secureFile->expires_at->format('M j, Y g:i A') }}</span>
                </div>
                @endif
            </div>

            @if($secureFile->password)
                <form method="POST" action="{{ route('secure-file.download', $secureFile->access_token) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password Required
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter password"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download File
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('secure-file.download', $secureFile->access_token) }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download File
                    </button>
                </form>
            @endif

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-lock mr-1"></i>
                    This file is protected and can only be downloaded {{ $secureFile->download_limit }} time{{ $secureFile->download_limit > 1 ? 's' : '' }}.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
