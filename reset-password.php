<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password | Missing Persons System</title>
  <!-- CSS Links -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <h2 class="text-center">Reset Password</h2>
        <form id="resetPasswordForm" method="POST" novalidate>
          <input type="hidden" id="token" value="<?php echo htmlspecialchars($_GET['token']); ?>" />
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" placeholder="Enter new password" required />
            <div class="invalid-feedback">Please enter your new password.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    resetPasswordForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;

      // Bootstrap form validation
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      const password = document.getElementById('password').value;
      const token = document.getElementById('token').value;

      try {
        const response = await fetch('update-password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ password, token })
        });

        const data = await response.json();
        alert(data.message);

        if (data.status === 'success') {
          window.location.href = 'admin-login.html'; // Redirect back to login page
        }
      } catch (error) {
        console.error('Error resetting password:', error);
        alert('An error occurred while resetting the password. Please try again.');
      }
    });
  </script>
</body>
</html>