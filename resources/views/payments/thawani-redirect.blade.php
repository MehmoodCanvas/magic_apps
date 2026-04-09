<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $status === 'success' ? 'Payment Successful' : 'Payment Cancelled' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: {{ $status === 'success' ? '#f0fdf4' : '#fef2f2' }};
        }
        .container {
            text-align: center;
            padding: 40px 20px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            color: {{ $status === 'success' ? '#16a34a' : '#dc2626' }};
            margin-bottom: 10px;
        }
        p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .loader {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #e5e7eb;
            border-top-color: {{ $status === 'success' ? '#16a34a' : '#dc2626' }};
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            @if($status === 'success')
                ✅
            @else
                ❌
            @endif
        </div>
        <h1>{{ $message }}</h1>
        <p>Redirecting back to app...</p>
        <div class="loader"></div>
    </div>

    <script>
        // Try deep link to redirect back to mobile app
        // Mobile WebView should intercept this URL scheme
        setTimeout(function() {
            window.location.href = "{{ $deep_link }}";
        }, 1500);
    </script>
</body>
</html>
