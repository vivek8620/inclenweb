<?php
/* Template Name: Custom Login Page */



// Get error message safely from URL
$error_msg = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'captcha_empty') {
        $error_msg = '⚠ Please complete the CAPTCHA.';
    } elseif ($_GET['error'] === 'captcha_failed') {
        $error_msg = '❌ CAPTCHA verification failed. Please try again.';
    }
} elseif (isset($_GET['login']) && $_GET['login'] === 'failed') {
    $error_msg = '❌ Invalid username or password.';
}

include __DIR__ . '/header-login.php';
?>

<main class="min-h-screen flex font-sans antialiased text-gray-900">
    <!-- Left Side: Image -->
    <div class="hidden lg:block lg:w-3/5 relative overflow-hidden">
        <img src="<?php echo esc_url(site_url('wp-content/themes/custom/assets/login_banner.png')); ?>"
            alt="Research Laboratory" class="absolute inset-0 w-full h-full object-cover">

    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full lg:w-2/5 flex flex-col items-center justify-center bg-gray-50 px-6 py-8 overflow-y-auto">
        <div class="w-full max-w-sm py-4">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="https://inclen.oakyweb.com/inclen_new.png" alt="INCLEN Logo"
                        class="h-16 w-auto mx-auto drop-shadow-sm">
                </a>
            </div>

            <!-- Form Header -->
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-heading font-bold text-gray-900 mb-1">Welcome Back</h2>
                <p class="text-sm text-gray-500">Please enter your credentials to access your account.</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_msg)): ?>
                <div class="mb-4 flex items-center justify-center p-3.5 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg shadow-sm" role="alert">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="font-semibold text-center"><?php echo esc_html(str_replace(['❌ ', '⚠ '], '', $error_msg)); ?></p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-4" method="post"
                action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>">
                <?php if (isset($_REQUEST['redirect_to'])): ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($_REQUEST['redirect_to']); ?>">
                <?php endif; ?>
                <div>
                    <label for="username" class="block text-xs font-medium text-gray-700 mb-1">Username or Email</label>
                    <input type="text" name="log" id="username"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none bg-white text-sm"
                        placeholder="Enter your username" autocomplete="username" required>
                    <p id="usernameError" class="hidden mt-1 text-[10px] text-red-500">Username or email is required</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-xs font-medium text-gray-700">Password</label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"
                            class="text-[11px] font-medium text-primary-600 hover:text-primary-700 transition-colors">
                            Forgot password?
                        </a>
                    </div>
                    <div class="relative">
                        <input type="password" name="pwd" id="password"
                            class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none bg-white text-sm"
                            placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg id="eyeOffIcon" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                    <p id="passwordError" class="hidden mt-1 text-[10px] text-red-500">Password is required</p>
                </div>

                <!-- reCAPTCHA -->
                <?php $is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false; ?>
                <?php if (!$is_localhost): ?>
                <div class="flex justify-center py-1 scale-90 origin-center">
                    <div class="g-recaptcha" data-sitekey="6LdGZj8tAAAAAEKJ2HPKiI6pdioq_Fh2JMyChYKW"></div>
                </div>
                <p id="captchaError" class="hidden text-center text-[10px] text-red-500">Please complete the CAPTCHA</p>
                <?php endif; ?>

                <div class="flex items-center">
                    <input type="checkbox" name="rememberme" id="rememberme" value="forever"
                        class="h-3.5 w-3.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded cursor-pointer">
                    <label for="rememberme" class="ml-2 block text-xs text-gray-600 cursor-pointer select-none">
                        Remember me
                    </label>
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full py-3 px-4 auth-gradient text-white rounded-lg font-semibold text-base shadow-md shadow-primary-500/20 hover:shadow-primary-500/30 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                    Sign In
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <p class="text-[11px] text-gray-500">
                    &copy; <?php echo date('Y'); ?> INCLEN. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</main>

<script>
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        let isValid = true;

        // Reset error states
        document.querySelectorAll('[id$="Error"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('input').forEach(el => el.classList.remove('border-red-500'));

        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const captcha = document.querySelector('.g-recaptcha-response');

        if (!username.value.trim()) {
            document.getElementById('usernameError').classList.remove('hidden');
            username.classList.add('border-red-500');
            isValid = false;
        }

        if (!password.value.trim()) {
            document.getElementById('passwordError').classList.remove('hidden');
            password.classList.add('border-red-500');
            isValid = false;
        }

        const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(location.hostname);
        if (captcha && !captcha.value && !isLocalhost) {
            document.getElementById('captchaError').classList.remove('hidden');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Signing in...</span>';
        btn.disabled = true;
    });

    // Toggle password visibility
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    if (togglePasswordBtn && passwordField) {
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            document.getElementById('eyeIcon').classList.toggle('hidden');
            document.getElementById('eyeOffIcon').classList.toggle('hidden');
        });
    }
</script>


</body>

</html>