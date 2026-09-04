/*=========================================================
=                 USUARIOS DE PRUEBA
=========================================================*/

const usuarios = [

    {
        nombre: "Leonardo",
        username: "admin",
        password: "admin123",
        rol: "Administrador"
    },

    {
        nombre: "Carlos",
        username: "vendedor",
        password: "venta123",
        rol: "Vendedor"
    },

    {
        nombre: "Andrés",
        username: "tecnico",
        password: "tec123",
        rol: "Tecnico"
    }

];


/*=========================================================
=                 INICIAR LOGIN
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {

    iniciarLogin();

});


function iniciarLogin(){

    configurarFormulario();

    configurarPassword();

}

/*=========================================================
=                 FORMULARIO
=========================================================*/

function configurarFormulario(){

    const formulario = document.getElementById("loginForm");

    formulario.addEventListener("submit", validarLogin);

}

/*=========================================================
=                 VALIDAR LOGIN
=========================================================*/

function validarLogin(e){

    e.preventDefault();

    const username = document.getElementById("username");

    const password = document.getElementById("password");

    const usuario = usuarios.find(u =>

        u.username === username.value.trim()

        &&

        u.password === password.value

    );

    if(!usuario){

        alert("Usuario o contraseña incorrectos.");

        password.value="";

        password.focus();

        return;

    }

    localStorage.setItem(

        "usuarioActivo",

        JSON.stringify(usuario)

    );

    window.location.href="pages/dashboard.html";

}

/*=========================================================
=                 MOSTRAR CONTRASEÑA
=========================================================*/

function configurarPassword(){

    const boton = document.getElementById("togglePassword");

    const input = document.getElementById("password");

    boton.addEventListener("click", ()=>{

        if(input.type==="password"){

            input.type="text";

            boton.innerHTML='<i class="fa-solid fa-eye-slash"></i>';

        }

        else{

            input.type="password";

            boton.innerHTML='<i class="fa-solid fa-eye"></i>';

        }

    });

}