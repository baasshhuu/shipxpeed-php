<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #121212;
            color: #e0e0e0;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(166, 86, 246, 0.1) 2%, transparent 0%),
                radial-gradient(circle at 75% 75%, rgba(102, 101, 241, 0.1) 2%, transparent 0%);
            background-size: 60px 60px;
        }

        .container {
            background-color: rgba(30, 30, 30, 0.8);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 400px;
            width: 100%;
        }

        h1 {
            margin-bottom: 1.5rem;
            color: #ffffff;
            font-weight: 600;
            font-size: 2rem;
        }

        p {
            margin-bottom: 2rem;
            color: #b0b0b0;
            font-weight: 300;
        }

        .otp-input {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-input input {
            width: 50px;
            height: 50px;
            margin: 0 8px;
            text-align: center;
            font-size: 1.5rem;
            border: 2px solid #6665F1;
            border-radius: 12px;
            background-color: rgba(42, 42, 42, 0.8);
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .otp-input input:focus {
            border-color: #A556F6;
            box-shadow: 0 0 0 2px rgba(166, 86, 246, 0.3);
            outline: none;
        }

        .otp-input input::-webkit-outer-spin-button,
        .otp-input input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .otp-input input[type=number] {
            -moz-appearance: textfield;
        }

        button {
            background: linear-gradient(135deg, #6665F1, #A556F6);
            color: white;
            border: 2px solid #6665F1;
            padding: 12px 24px;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        button:hover {
            background: linear-gradient(135deg, #A556F6, #6665F1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(166, 86, 246, 0.3);
        }

        button:disabled {
            background: #cccccc;
            border-color: #999999;
            color: #666666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        #email {
            color: #A556F6;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="">
        <h1>OTP Verification</h1>

        <form method="POST" action="{{ route('seller.verify.otp') }}">
            @csrf
            <div class="otp-input">
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
            </div>

            <input type="hidden" name="otp" id="otp-hidden">
            <button type="submit" onclick="return prepareOTP()">Verify</button>
        </form>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input input');
        const hiddenInput = document.getElementById('otp-hidden');

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                if (value.length > 1) {
                    // Handle case where user pastes multiple digits
                    const digits = value.split('');
                    for (let i = 0; i < digits.length && index + i < inputs.length; i++) {
                        inputs[index + i].value = digits[i];
                    }
                    const nextIndex = Math.min(index + digits.length, inputs.length - 1);
                    inputs[nextIndex].focus();
                } else {
                    e.target.value = value; // Keep only one digit
                    if (value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
                if (!/[0-9]|Backspace|Tab/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        function prepareOTP() {
            const otp = Array.from(inputs).map(input => input.value).join('');
            if (otp.length === 6) {
                hiddenInput.value = otp;
                return true;
            } else {
                alert('Please enter a 6-digit OTP');
                return false;
            }
        }
    </script>

</body>

</html>
