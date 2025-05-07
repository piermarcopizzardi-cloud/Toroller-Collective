<?php

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Example: Validate credentials (replace with your database logic)
    if ($username === 'admin' && $password === 'password') {
        echo "Login successful!";
    } else {
        echo "Invalid username or password.";
    }
}
?>

<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file >
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html> -->


<div style="width: 100%; height: 100%; position: relative; background: white; overflow: hidden">
    <div style="width: 1440px; height: 934px; left: 0px; top: 0px; position: absolute; background: white"></div>
    <div style="height: 482px; padding-left: 40px; padding-right: 40px; padding-top: 72px; padding-bottom: 72px; left: 734px; top: 218px; position: absolute; background: #F1FAF2; box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25); border-radius: 30px; flex-direction: column; justify-content: flex-start; align-items: center; display: inline-flex">
        <div style="flex-direction: column; justify-content: flex-start; align-items: center; gap: 32px; display: flex">
            <div style="justify-content: flex-start; align-items: flex-start; gap: 24px; display: inline-flex">
                <div style="flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 32px; display: inline-flex">
                    <div style="flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 12px; display: flex">
                        <div style="color: #141212; font-size: 18px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word"> E-mail</div>
                        <div style="width: 246px; height: 72px; position: relative">
                            <div style="width: 246px; height: 72px; left: 0px; top: 0px; position: absolute">
                                <div style="width: 246px; height: 72px; left: 0px; top: 0px; position: absolute; background: white; border-radius: 50px"></div>
                                <div style="left: 24px; top: 27px; position: absolute; justify-content: flex-start; align-items: center; gap: 10px; display: inline-flex">
                                    <div style="color: #92D2AD; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">example@email.com</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 12px; display: flex">
                        <div style="color: #141212; font-size: 18px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word">Password</div>
                        <div style="width: 246px; height: 72px; position: relative">
                            <div style="width: 246px; height: 72px; left: 0px; top: 0px; position: absolute">
                                <div style="width: 246px; height: 72px; left: 0px; top: 0px; position: absolute; background: white; border-radius: 50px"></div>
                                <div style="left: 24px; top: 27px; position: absolute; justify-content: flex-start; align-items: center; gap: 10px; display: inline-flex">
                                    <div style="color: #92D2AD; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Password</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div data-color="Default" data-icon-left="False" data-icon-right="False" data-size="Default" style="width: 426px; justify-content: flex-end; align-items: center; display: inline-flex">
                <div data-size="Default" style="flex: 1 1 0; padding-left: 38px; padding-right: 38px; padding-top: 26px; padding-bottom: 26px; background: #04CD00; border-radius: 50px; justify-content: center; align-items: center; gap: 6px; display: flex">
                    <div style="text-align: center; color: white; font-size: 16px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word">Get started</div>
                </div>
            </div>
        </div>
    </div>
    <img style="width: 408.98px; height: 437.57px; left: 2.28px; top: 526.34px; position: absolute; transform: rotate(-22deg); transform-origin: top left" src="https://placehold.co/409x438" />
    <img style="width: 272.06px; height: 382.96px; left: 503px; top: 77.26px; position: absolute; transform: rotate(-2deg); transform-origin: top left" src="https://placehold.co/272x383" />
    <div style="width: 546.98px; height: 132px; left: 110px; top: 280px; position: absolute; color: #04CD00; font-size: 56px; font-family: DM Sans; font-weight: 700; line-height: 66px; word-wrap: break-word">Resta connesso alle tue passioni</div>
    <div style="width: 351px; height: 26px; left: 110px; top: 628px; position: absolute"></div>
    <div style="width: 1440px; height: 118px; left: 0px; top: 0px; position: absolute">
        <div style="width: 651px; padding-left: 25px; left: 679px; top: 32.07px; position: absolute; justify-content: flex-end; align-items: center; gap: 24px; display: inline-flex">
            <div style="justify-content: flex-end; align-items: center; gap: 33px; display: flex">
                <div click="index.php" style="text-align: center; color: #BDD3C6; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Home</div>
                <div style="text-align: center; color: #BDD3C6; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Comm–unity</div>
                <div style="justify-content: flex-start; align-items: center; gap: 10px; display: flex">
                    <div style="text-align: center; color: #BDD3C6; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Shop</div>
                    <div style="width: 11.67px; height: 5.83px; outline: 1.40px #211F54 solid; outline-offset: -0.70px"></div>
                </div>
                <div style="text-align: center; color: #BDD3C6; font-size: 18px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Eventi</div>
            </div>
            <div data-icon-left="false" data-icon-right="false" data-size="Small" data-type="Primary" style="padding-left: 24px; padding-right: 24px; padding-top: 18px; padding-bottom: 18px; background: white; border-radius: 30px; outline: 1px #7FE47E solid; outline-offset: -1px; justify-content: flex-end; align-items: center; gap: 8px; display: flex">
                <div style="text-align: center; color: #BDD3C6; font-size: 16px; font-family: DM Sans; font-weight: 400; line-height: 18px; word-wrap: break-word">Login</div>
            </div>
            <div data-icon-left="false" data-icon-right="false" data-size="Small" data-type="Primary" style="padding-left: 24px; padding-right: 24px; padding-top: 18px; padding-bottom: 18px; background: #04CD00; border-radius: 30px; justify-content: flex-end; align-items: center; gap: 8px; display: flex">
                <div style="text-align: center; color: white; font-size: 16px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word">Get started</div>
            </div>
        </div>
        <div style="width: 261.31px; height: 34.35px; left: 110px; top: 41.83px; position: absolute">
            <img style="width: 61px; height: 80.09px; left: -8px; top: -27.83px; position: absolute" src="https://placehold.co/61x80" />
        </div>
        <div style="width: 614px; height: 96.95px; left: 0px; top: 54.03px; position: absolute; text-align: center; color: #04CD00; font-size: 30px; font-family: Inter; font-weight: 800; line-height: 18px; word-wrap: break-word">TorollerCollective</div>
    </div>
    <div style="left: 110px; top: 490px; position: absolute; justify-content: flex-start; align-items: flex-start; display: inline-flex">
        <div style="justify-content: flex-start; align-items: center; gap: 14px; display: flex">
            <div style="width: 26px; height: 26px; position: relative; overflow: hidden">
                <div style="width: 26px; height: 26px; left: 0px; top: 0px; position: absolute; background: #04CD00"></div>
                <div style="width: 11.77px; height: 8.40px; left: 7.12px; top: 8.80px; position: absolute; outline: 2px white solid; outline-offset: -1px"></div>
            </div>
            <div style="color: #04CD00; font-size: 18px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word">La tua privacy e la nostra priorità</div>
        </div>
    </div>
    <div style="left: 110px; top: 532px; position: absolute; justify-content: flex-start; align-items: center; gap: 14px; display: inline-flex">
        <div style="width: 26px; height: 26px; position: relative; overflow: hidden">
            <div style="width: 26px; height: 26px; left: 0px; top: 0px; position: absolute; background: #04CD00"></div>
            <div style="width: 11.77px; height: 8.40px; left: 7.12px; top: 8.80px; position: absolute; background: #04CD00; outline: 2px white solid; outline-offset: -1px"></div>
        </div>
        <div style="color: #04CD00; font-size: 18px; font-family: DM Sans; font-weight: 700; line-height: 18px; word-wrap: break-word">utilizziamo sistemi di crittografia nel vostro rispetto</div>
    </div>
</div>