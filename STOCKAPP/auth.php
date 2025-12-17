<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Trade - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
        .modal-backdrop { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    </style>
</head>
<body class="h-screen flex items-center justify-center bg-white p-6 relative">
    
    <!-- MAIN FORM -->
    <div class="w-full max-w-md space-y-8 transition-all duration-300" id="mainContainer">
        <div class="text-center">
            <h1 class="text-6xl font-black tracking-tighter">TRADE.</h1>
            <p class="text-gray-400 mt-2">Real-Time Market Simulation</p>
        </div>
        
        <div class="bg-gray-50 p-8 rounded-3xl border border-gray-100 shadow-xl">
            <div class="flex gap-4 mb-6">
                <button onclick="setMode('login')" id="tabLogin" class="flex-1 py-2 font-bold border-b-2 border-black">Login</button>
                <button onclick="setMode('register')" id="tabRegister" class="flex-1 py-2 font-bold text-gray-400 border-b-2 border-transparent">Register</button>
            </div>

            <div class="space-y-4">
                <input type="text" id="user" placeholder="Username" class="w-full p-4 bg-white rounded-xl border border-gray-200 outline-none focus:border-black transition">
                
                <!-- Email Input (Hidden on Login) -->
                <input type="email" id="email" placeholder="Email Address" class="w-full p-4 bg-white rounded-xl border border-gray-200 outline-none focus:border-black transition hidden">
                
                <input type="password" id="pass" placeholder="Password" class="w-full p-4 bg-white rounded-xl border border-gray-200 outline-none focus:border-black transition">
                <button onclick="submit()" id="btnSubmit" class="w-full bg-black text-white py-4 rounded-xl font-bold hover:scale-[1.02] transition shadow-lg">Proceed</button>
            </div>
            <!-- Error text replaced by Modal, but keeping a small placeholder just in case -->
            <p id="err" class="text-red-500 text-center mt-4 text-sm font-bold min-h-[20px] hidden"></p>
        </div>
    </div>

    <!-- PIN MODAL (Hidden initially) -->
    <div id="otpModal" class="absolute inset-0 bg-white z-50 hidden flex-col items-center justify-center p-6">
        <div class="max-w-sm w-full text-center">
            <div class="mb-6 bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto text-blue-600">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <h2 class="text-3xl font-bold mb-2">Check your Email</h2>
            <p class="text-gray-500 mb-8">We sent a verification code to <span id="otpEmailDisplay" class="font-bold text-black"></span></p>
            
            <input type="text" id="otpInput" maxlength="6" placeholder="000000" class="w-full p-4 text-center text-4xl font-mono tracking-widest bg-gray-50 rounded-xl border-2 border-gray-200 focus:border-black outline-none mb-6">
            
            <button onclick="verifyOtp()" class="w-full bg-black text-white py-4 rounded-xl font-bold">Verify Code</button>
            <button onclick="location.reload()" class="mt-4 text-gray-400 text-sm hover:text-black">Cancel</button>
        </div>
    </div>

    <!-- CUSTOM ALERT MODAL (Added for Auth) -->
    <div id="alertModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 modal-backdrop transition-opacity duration-200">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl transform scale-95 modal-content transition-all duration-200 m-4 text-center" id="alertBox">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4" id="alertIcon">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-bold mb-2" id="alertTitle">Success</h3>
            <p class="text-gray-500 mb-6" id="alertText">Operation completed.</p>
            <button onclick="window.closeAlert()" class="w-full py-3 rounded-xl bg-black text-white font-bold hover:bg-gray-800 transition shadow-lg">Okay</button>
        </div>
    </div>

    <script>
        let mode = 'login';
        let pendingEmail = '';

        // --- MODAL UTILS ---
        window.closeAlertCallback = null;
        window.showAlert = function(title, text, isError = false) {
            return new Promise((resolve) => {
                const modal = document.getElementById('alertModal');
                const box = document.getElementById('alertBox');
                const icon = document.getElementById('alertIcon');
                
                document.getElementById('alertTitle').innerText = title;
                document.getElementById('alertText').innerText = text;

                if(isError) {
                    icon.className = "w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4";
                    icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>';
                } else {
                    icon.className = "w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4";
                    icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                }
                
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    box.classList.remove('scale-95');
                    box.classList.add('scale-100');
                }, 10);
                
                window.closeAlertCallback = () => {
                    box.classList.remove('scale-100');
                    box.classList.add('scale-95');
                    modal.classList.add('opacity-0');
                    setTimeout(() => { modal.classList.add('hidden'); resolve(); }, 200);
                };
            });
        };
        window.closeAlert = function() {
            if (window.closeAlertCallback) window.closeAlertCallback();
        };

        // --- AUTH LOGIC ---
        function setMode(m) {
            mode = m;
            document.getElementById('email').classList.toggle('hidden', m === 'login');
            document.getElementById('tabLogin').className = m==='login' ? 'flex-1 py-2 font-bold border-b-2 border-black' : 'flex-1 py-2 font-bold text-gray-400 border-b-2 border-transparent';
            document.getElementById('tabRegister').className = m==='register' ? 'flex-1 py-2 font-bold border-b-2 border-black' : 'flex-1 py-2 font-bold text-gray-400 border-b-2 border-transparent';
        }

        async function submit() {
            const u = document.getElementById('user').value;
            const p = document.getElementById('pass').value;
            const e = document.getElementById('email').value;
            
            const payload = {username: u, password: p};
            if(mode === 'register') payload.email = e;

            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerText = "Processing...";

            try {
                const res = await fetch('api.php?action='+mode, {method:'POST', body:JSON.stringify(payload)});
                const data = await res.json();
                
                if (data.success) {
                    if (data.requires_otp === false || mode === 'login') {
                        // Success - Show Modal then Redirect
                        await showAlert("Welcome Back!", "Redirecting to market...", false);
                        location.href = 'market.php';
                    } else {
                        pendingEmail = data.email;
                        showOtp(pendingEmail);
                    }
                } else if (data.requires_otp) {
                    pendingEmail = data.email;
                    showOtp(pendingEmail);
                } else {
                    await showAlert("Error", data.error || "Authentication failed", true);
                }
            } catch(err) {
                await showAlert("Connection Error", "Could not reach server.", true);
            } finally {
                btn.disabled = false;
                btn.innerText = "Proceed";
            }
        }

        function showOtp(email) {
            document.getElementById('mainContainer').classList.add('hidden');
            document.getElementById('otpModal').classList.remove('hidden');
            document.getElementById('otpModal').classList.add('flex');
            document.getElementById('otpEmailDisplay').innerText = email;
        }

        async function verifyOtp() {
            const code = document.getElementById('otpInput').value;
            const res = await fetch('api.php?action=verify_otp', {
                method: 'POST', body: JSON.stringify({email: pendingEmail, code: code})
            });
            const data = await res.json();
            
            if(data.success) {
                await showAlert("Verified!", "Logging you in...", false);
                location.href = 'market.php';
            } else {
                await showAlert("Invalid Code", "Please try again.", true);
            }
        }
    </script>
</body>
</html>