function alternarForm() {
    const login = document.getElementById('formLogin');
    const registro = document.getElementById('formRegistro');
    if (login.style.display !== 'none') {
        login.style.display = 'none';
        registro.style.display = 'block';
    } else {
        login.style.display = 'block';
        registro.style.display = 'none';
    }
}
function fazerLogin(event) {
    event.preventDefault();
    window.location.href = '../views/entregas.html';
}
function fazerRegistro(event) {
    event.preventDefault();
    const senha = document.getElementById('registroSenha').value;
    const senha2 = document.getElementById('registroSenha2').value;
    if (senha !== senha2) {
        alert('As senhas não coincidem!');
        return;
    }
    window.location.href = '../views/entregas.html';
}