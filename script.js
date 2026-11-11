document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');

  loginForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    // Contoh penanganan login sederhana
    if (email && password) {
      alert(`Selamat datang di Abby Collection!\nEmail: ${email}`);
      // Di sini bisa ditambahkan logika AJAX / Fetch ke backend
    }
  });
});