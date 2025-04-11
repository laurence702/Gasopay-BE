<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Gasopay</title>
</head>
<body>
    <h1>Welcome to Gasopay!</h1>
    
    <p>Hello {{ $user->fullname }},</p>
    
    <p>Welcome to Gasopay! Your account has been successfully created.</p>
    
    <p>Here are your login credentials:</p>
    <ul>
        <li>Email: {{ $user->email }}</li>
        <li>Password: {{ $password }}</li>
    </ul>
    
    <p>For security reasons, we recommend changing your password after your first login.</p>
    
    <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
    
    <p>Best regards,<br>The Gasopay Team</p>
</body>
</html> 