<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    session_start();
}

$isLoggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);
if ($isLoggedIn) {
    // Redirect to their profile page
    $redirect = $configManager->getBaseUrl() . 'pages/myProfile.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

$title = 'Reset Password';
include_once PUBLIC_FILES . '/modules/header.php';

?>

<section class="vh-100" style="background-color: #D73F09;">
    <form action="/pages/resetPasswordSubmit.php" method="POST">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-2-strong" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center" style="background-color: #D1D1D1">
                            <h3 class="mb-5">Please enter a new password:</h3>
                            <div class="form-outline mb-4">
                                <label class="form-label" for="newUserPassword"></label>
                                <input type="password" name="newUserPassword" id="newUserPassword" class="form-control form-control-lg" />
                                <br>
                                <input type="checkbox" onclick="togglePasswordVisibility()"> Show Password
                            </div>
                            <button class="btn btn-dark btn-lg btn-block" type="submit">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
    function togglePasswordVisibility() {
        var x = document.getElementById("newUserPassword");
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }

</script>

<?php include_once PUBLIC_FILES . '/modules/footer.php'; ?>