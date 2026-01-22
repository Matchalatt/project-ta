<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login & Register</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            /* ====================================================== */
            /* ============ AWAL PERUBAHAN PALET WARNA ============== */
            /* ====================================================== */

            --primary-color: #F97316;   /* Tailwind Orange 500 */
            --secondary-color: #C2410C; /* Tailwind Orange 700 */
            
            /* ====================================================== */
            /* ============= AKHIR PERUBAHAN PALET WARNA ============ */
            /* ====================================================== */

            --white-color: #FFFFFF;
            --light-gray-color: #f6f5f7;
            --input-bg-color: #eee;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
            background-image: url("{{ asset('images/background.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* ====================================================== */
            /* === PERUBAHAN WARNA PADA GRADIENT ANIMASI Latar Belakang === */
            /* ====================================================== */
            background:
                radial-gradient(circle at 20% 50%, rgba(249, 115, 22, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(194, 65, 12, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(249, 115, 22, 0.06) 0%, transparent 50%);
            animation: subtleShift 8s ease-in-out infinite;
            z-index: -1;
        }
        
        /* Gaya untuk notifikasi (tidak diubah karena merupakan warna standar UI) */
        .notification {
            position: absolute; 
            top: 15px; 
            left: 50%; 
            transform: translateX(-50%); 
            color: white; 
            padding: 10px 20px; 
            border-radius: 5px; 
            z-index: 200; 
            font-size: 14px; 
            text-align: center; 
            max-width: 90%;
            transition: opacity 0.5s ease-out;
        }
        .notification.success { background-color: #28a745; }
        .notification.error { background-color: #dc3545; text-align: left; }
        .notification.hidden { opacity: 0; pointer-events: none; }

        @keyframes subtleShift {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.05); }
        }

        h1 { font-weight: bold; margin: 0; }
        p { font-size: 14px; font-weight: 100; line-height: 20px; letter-spacing: 0.5px; margin: 20px 0 30px; }
        a { color: #333; font-size: 14px; text-decoration: none; margin: 15px 0; display: block; height: 20px; line-height: 20px; }
        button { border-radius: 20px; border: 1px solid var(--primary-color); background-color: var(--primary-color); color: var(--white-color); font-size: 12px; font-weight: bold; padding: 12px 45px; letter-spacing: 1px; text-transform: uppercase; transition: transform 80ms ease-in; cursor: pointer; }
        button:active { transform: scale(0.95); }
        button:focus { outline: none; }
        button.ghost { background-color: transparent; border-color: var(--white-color); }
        form { background-color: var(--white-color); display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 50px; height: 100%; text-align: center; }
        input { background-color: var(--input-bg-color); border: none; padding: 12px 15px; margin: 8px 0; width: 100%; border-radius: 5px; box-sizing: border-box; }
        .sign-in-container form, .sign-up-container form { min-height: 400px; position: relative; }
        .sign-in-container form > *, .sign-up-container form > * { margin: 8px 0; }
        .sign-in-container form h1, .sign-up-container form h1 { margin: 0 0 30px 0; }
        .sign-in-container form button, .sign-up-container form button { margin: 30px 0 0 0; }
        .container { background-color: var(--white-color); border-radius: 15px; box-shadow: 0 25px 50px rgba(0,0,0,0.4), 0 20px 30px rgba(0,0,0,0.3); position: relative; overflow: hidden; width: 768px; max-width: 100%; min-height: 480px; backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.3); }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); }
        .container.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s; }
        @keyframes show { 0%, 49.99% { opacity: 0; z-index: 1; } 50%, 100% { opacity: 1; z-index: 5; } }
        .overlay-container { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }
        .overlay { position: relative; color: var(--white-color); left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        #background-video { position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; z-index: -1; transform: translateX(-50%) translateY(-50%); object-fit: cover; }
        .overlay::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); z-index: 0; }
        .container.right-panel-active .overlay { transform: translateX(50%); }
        .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out; z-index: 1; }
        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }
        .mobile-card-wrapper { display: none; }

        @media (max-width: 768px) {
            .container { background-color: transparent; box-shadow: none; border: none; width: 100%; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
            .overlay-container, .form-container { display: none; }
            .mobile-card-wrapper { display: flex; position: relative; width: 90%; max-width: 400px; height: 520px; border-radius: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.5); background-color: var(--white-color); }
            .mobile-card-wrapper #background-video { z-index: 0; border-radius: 15px 0 0 15px; }
            .mobile-card-container { flex-grow: 1; position: relative; overflow: hidden; border-radius: 15px 0 0 15px; }
            .mobile-form-container { position: absolute; top: 0; width: 100%; height: 100%; transition: transform 0.6s ease-in-out; z-index: 1; }
            .sign-in-container-mobile { left: 0; transform: translateX(0); }
            .sign-up-container-mobile { left: 0; transform: translateX(100%); }
            .container.right-panel-active .sign-in-container-mobile { transform: translateX(-100%); }
            .container.right-panel-active .sign-up-container-mobile { transform: translateX(0); }
            .mobile-toggle-container { display: flex; justify-content: center; align-items: center; flex-shrink: 0; width: 45px; background-color: var(--primary-color); border-radius: 0 15px 15px 0; position: relative; z-index: 10; }
            .mobile-toggle-btn { writing-mode: vertical-rl; text-orientation: mixed; padding: 10px 0; background-color: transparent; color: var(--white-color); border: 1px solid var(--white-color); border-radius: 15px; box-shadow: none; width: 30px; height: 120px; font-size: 11px; }
            #mobileSignInBtn { display: none; }
            .container.right-panel-active #mobileSignInBtn { display: block; }
            .container.right-panel-active #mobileSignUpBtn { display: none; }
        }
    </style>
</head>
<body>
    <div class="container {{ $errors->has('no_tlpn') || $errors->has('password_confirmation') ? 'right-panel-active' : '' }}" id="container">
        
        @if (session('success'))
            <div class="notification success" id="notification-panel">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="notification error" id="notification-panel">
                <ul style="margin: 0; padding-left: 15px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="notification error hidden" id="client-notification-panel"></div>

        <div class="form-container sign-up-container">
            <form action="{{ route('register') }}" method="POST" autocomplete="off" id="registerFormDesktop">
                @csrf
                <h1>Create Account</h1>
                <input type="text" placeholder="Username" name="username" required value="{{ old('username') }}" />
                <input type="tel" placeholder="No. Tlpn" name="no_tlpn" required value="{{ old('no_tlpn') }}" />
                <input type="password" placeholder="Password" name="password" required id="passwordDesktop" />
                <input type="password" placeholder="Confirm Password" name="password_confirmation" required />
                <button type="submit">Register</button>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form action="{{ route('login') }}" method="POST" autocomplete="off">
                @csrf
                <h1>Login</h1>
                <input type="text" placeholder="Username" name="username" required value="{{ old('username') }}" />
                <input type="password" placeholder="Password" name="password" required />
                <a href="#">Forgot your password?</a>
                <button type="submit">Login</button>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <video autoplay muted loop playsinline id="background-video">
                    <source src="{{ asset('video/Video_Jajanan_Pasar_Detik_tanpa_audio.mp4') }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Login</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp">Register</button>
                </div>
            </div>
        </div>
        
        <div class="mobile-card-wrapper">
            <div class="mobile-card-container">
                <video autoplay muted loop playsinline id="background-video">
                    <source src="{{ asset('video/Video_Jajanan_Pasar_Detik_tanpa_audio.mp4') }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="mobile-form-container sign-up-container-mobile">
                    <form action="{{ route('register') }}" method="POST" autocomplete="off" id="registerFormMobile">
                        @csrf
                        <h1>Create Account</h1>
                        <input type="text" placeholder="Username" name="username" required value="{{ old('username') }}" />
                        <input type="tel" placeholder="No. Tlpn" name="no_tlpn" required value="{{ old('no_tlpn') }}" />
                        <input type="password" placeholder="Password" name="password" required id="passwordMobile" />
                        <input type="password" placeholder="Confirm Password" name="password_confirmation" required />
                        <button type="submit">Register</button>
                    </form>
                </div>
                <div class="mobile-form-container sign-in-container-mobile">
                    <form action="{{ route('login') }}" method="POST" autocomplete="off">
                        @csrf
                        <h1>Login</h1>
                        <input type="text" placeholder="Username" name="username" required value="{{ old('username') }}" />
                        <input type="password" placeholder="Password" name="password" required />
                        <a href="#">Forgot your password?</a>
                        <button type="submit">Login</button>
                    </form>
                </div>
            </div>
            <div class="mobile-toggle-container">
                <button class="mobile-toggle-btn" id="mobileSignUpBtn">Register</button>
                <button class="mobile-toggle-btn" id="mobileSignInBtn">Login</button>
            </div>
        </div>
    </div>

    <script>
        // Skrip ini tidak diubah sama sekali karena hanya mengatur fungsionalitas
        const container = document.getElementById('container');
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const mobileSignUpBtn = document.getElementById('mobileSignUpBtn');
        const mobileSignInBtn = document.getElementById('mobileSignInBtn');

        if (signUpButton) {
            signUpButton.addEventListener('click', () => {
                container.classList.add('right-panel-active');
            });
        }
        if (signInButton) {
            signInButton.addEventListener('click', () => {
                container.classList.remove('right-panel-active');
            });
        }
        if (mobileSignUpBtn) {
            mobileSignUpBtn.addEventListener('click', () => {
                container.classList.add('right-panel-active');
            });
        }
        if (mobileSignInBtn) {
            mobileSignInBtn.addEventListener('click', () => {
                container.classList.remove('right-panel-active');
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const notificationPanel = document.getElementById('notification-panel');
            if (notificationPanel) {
                setTimeout(() => {
                    notificationPanel.classList.add('hidden');
                }, 3000);
            }
            
            const registerFormDesktop = document.getElementById('registerFormDesktop');
            const passwordDesktop = document.getElementById('passwordDesktop');
            const registerFormMobile = document.getElementById('registerFormMobile');
            const passwordMobile = document.getElementById('passwordMobile');
            const clientNotificationPanel = document.getElementById('client-notification-panel');

            const showClientNotification = (message) => {
                if (clientNotificationPanel) {
                    clientNotificationPanel.textContent = message;
                    clientNotificationPanel.classList.remove('hidden');
                    setTimeout(() => {
                        clientNotificationPanel.classList.add('hidden');
                    }, 3000);
                }
            };
            
            if (registerFormDesktop) {
                registerFormDesktop.addEventListener('submit', function(event) {
                    event.preventDefault();
                    if (passwordDesktop.value.length < 8) {
                        showClientNotification('Password harus terdiri dari minimal 8 karakter.');
                    } else {
                        this.submit();
                    }
                });
            }

            if (registerFormMobile) {
                registerFormMobile.addEventListener('submit', function(event) {
                    event.preventDefault();
                    if (passwordMobile.value.length < 8) {
                        showClientNotification('Password harus terdiri dari minimal 8 karakter.');
                    } else {
                        this.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>