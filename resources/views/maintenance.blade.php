<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>We'll Be Back Soon</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8f9fa;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      text-align: center;
      color: #333;
    }

    .container {
      max-width: 600px;
      padding: 20px;
    }

    h1 {
      font-size: 3em;
      margin-bottom: 20px;
      color: #dc3545;
    }

    p {
      font-size: 1.2em;
      line-height: 1.5;
    }

    .spinner {
      margin: 20px auto;
      width: 40px;
      height: 40px;
      border: 4px solid #ccc;
      border-top: 4px solid #dc3545;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="spinner"></div>
    <h1>We'll Be Back Soon!</h1>
    <p>Our website is currently undergoing scheduled maintenance.<br>
    We appreciate your patience and will be back online shortly.</p>
  </div>
</body>
</html>
