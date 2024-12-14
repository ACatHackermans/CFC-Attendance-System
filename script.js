// Displays a message when typing password in register page
function check() {
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm_password').value;
  const submit = document.getElementById('submit'); 
  const message = document.getElementById('message');
  message.innerHTML = '';

  if (password.length < 8) {
    message.style.color = '#ef476f';
    message.style.margin = 0;
    message.innerHTML = 'Password must contain at least 8 characters.';
    submit.disabled = true;
    return;
  }

  if (password !== confirmPassword) {
    message.style.color = '#ef476f';
    message.innerHTML = 'Password does not match';
    submit.disabled = true;
    return;
  }

  submit.disabled = false;
}