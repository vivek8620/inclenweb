<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | INCLEN</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Reliable CSS Version) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        /* CRITICAL CSS - Works even if Tailwind fails */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: #f9fafb; overflow: hidden; }
        .min-h-screen { min-height: 100vh; }
        .flex { display: flex; }
        .w-full { width: 100%; }
        .hidden { display: none; }
        @media (min-width: 1024px) {
            .lg\:block { display: block; }
            .lg\:w-3\/5 { width: 60%; }
            .lg\:w-2\/5 { width: 40%; }
        }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
        .object-cover { object-fit: cover; }
        .bg-gray-50 { background-color: #f9fafb; }
        .items-center { align-items: center; }
        .justify-center { justify-content: center; }
        .px-8 { padding-left: 2rem; padding-right: 2rem; }
        .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
        .max-w-md { max-width: 28rem; }
        .mb-10 { margin-bottom: 2.5rem; }
        .h-16 { height: 4rem; }
        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
        .font-bold { font-weight: 700; }
        .text-gray-900 { color: #111827; }
        .text-gray-500 { color: #6b7280; }
        .space-y-6 > * + * { margin-top: 1.5rem; }
        .block { display: block; }
        .text-sm { font-size: 0.875rem; }
        .font-medium { font-weight: 500; }
        .rounded-xl { border-radius: 0.75rem; }
        .border { border: 1px solid #e5e7eb; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .auth-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="login-custom-page">
