
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="We have a wide range of vehicles for Sale">
    <title>Contact Page</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="javascript/script.js"></script>
       
</head>
<body class="body" background="products/sky.jpg">
    
<?php include "include/nav.html" ?>
<?php include "include/header.html" ?>

<main class="con">
        <div class="container">
     <h2>Contact Us </h2>
     <form  onsubmit="submitForm(event)" method="post" action="register.php">
     <label for="name">Name</label>
     <input type="text" id="name" name="name" required>
<br>
     <label for="email">Email</label>
     <input type="email" id="email" name="email" required>
<br>
     <label for="message">Message</label>
     <input type="message" id="message" name="message" rows="5" required>
<br>
<button type="submit" >SUBMIT</button>
    <div id="response"></div>
     </form>
     
     </div>
    </main>

    <?php include "include/footer.html" ?>
     
</body>
</html>
