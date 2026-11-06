document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('loginForm');
    const campoUsuario = document.getElementById('username');
    const campoContrasena = document.getElementById('password');
    const botonMostrar = document.getElementById('togglePassword');
    const mensaje = document.getElementById('loginMessage');
    const botonEnviar = formulario?.querySelector('[type="submit"]');

    const mostrarMensaje = (texto, tipo = 'error') => {
        if (!mensaje) return;
        mensaje.textContent = texto;
        mensaje.hidden = false;
        mensaje.classList.toggle('success', tipo === 'success');
    };

    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get('inicializado') === '1') mostrarMensaje('Administrador inicial creado. Ya puedes iniciar sesión.', 'success');
    if (parametros.get('restablecida') === '1') mostrarMensaje('Contraseña actualizada. Inicia sesión con la nueva contraseña.', 'success');
    if (parametros.get('motivo') === 'inactivo') mostrarMensaje('La cuenta está inactiva. Contacta al administrador.');

    formulario?.addEventListener('submit', async evento => {
        evento.preventDefault();
        if (mensaje) mensaje.hidden = true;
        if (botonEnviar) {
            botonEnviar.disabled = true;
            botonEnviar.dataset.textoOriginal = botonEnviar.innerHTML;
            botonEnviar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Validando…';
        }

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                body: new FormData(formulario),
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.ok) {
                mostrarMensaje(datos.mensaje || 'No fue posible iniciar sesión.');
                campoContrasena.value = '';
                campoContrasena.focus();
                return;
            }

            try {
                localStorage.setItem('usuarioActivo', JSON.stringify(datos.usuario));
            } catch (error) {
                // La sesión PHP es la que autoriza el acceso; localStorage solo alimenta el perfil visual.
            }
            window.location.assign(datos.redirect || 'pages/dashboard.php');
        } catch (error) {
            mostrarMensaje('No se pudo conectar con el servidor. Verifica Apache y MySQL.');
        } finally {
            if (botonEnviar) {
                botonEnviar.disabled = false;
                botonEnviar.innerHTML = botonEnviar.dataset.textoOriginal || '<i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión';
            }
        }
    });

    botonMostrar?.addEventListener('click', () => {
        const mostrar = campoContrasena.type === 'password';
        campoContrasena.type = mostrar ? 'text' : 'password';
        botonMostrar.innerHTML = mostrar
            ? '<i class="fa-solid fa-eye-slash"></i>'
            : '<i class="fa-solid fa-eye"></i>';
        botonMostrar.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
});
