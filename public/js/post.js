const POST_LIKE_ROUTE = Routing.generate('likes');
const COMMENT_EDIT_ROUTE = Routing.generate('comment_edit');
const LOADING_BUTTON_CONTENT = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>\n' +
    '  <span class="visually-hidden" role="status">Cargando</span>';
const toastNotification = document.getElementById('liveToast')

window.addEventListener("load", (event) => {
    // Obtengo los botones utilizados para editar comentarios
    let buttonList = document.querySelectorAll('button[id*="commentEditButton"]');
    let buttonListLength = buttonList.length;

    for (let i = 0; i < buttonListLength; i++ ) {
        buttonList[i].addEventListener( 'click', (event) => {

            // ID del comentario a editar
            let commentId = event.target.id.replace(/[^0-9]/g, '');

            // Contenedor del comentario
            let commentContainer = document.getElementById(`commentContent${commentId}`);

            // Obtengo el comentario original
            let originalComment = commentContainer.getElementsByTagName('p')[0];

            // Creo los contenidos del nuevo comentario y los inserto
            let newComment = createCommentElements(commentId);
            commentContainer.appendChild(newComment);

            // Escondo el comentario actual y muestro la edición
            hideElement(buttonList[i]);
            hideElement(originalComment);

            // Guardo el nuevo comentario
            let editableComment = document.getElementById(`commentEdit${commentId}`);

            // Abro el collapse, mostrando el contenido
            const bsCollapse = new bootstrap.Collapse(`#commentEdit${commentId}`, {
                toggle: true,
            })

            // Obtengo los distintos elementos de la nueva edición de comentario
            let commentTextarea = document.getElementById(`commentEditTextarea${commentId}`);
            commentTextarea.value = originalComment.innerText;

            let commentErrorMessage = document.getElementById(`commentEditError${commentId}`);
            commentErrorMessage.textContent = ''; // Si ya existe el error lo dejo vacio

            let submitButton = document.getElementById(`commentEditSubmit${commentId}`);
            let exitButton = document.getElementById(`commentEditExit${commentId}`);


            exitButton.addEventListener('click', (event) => {
                // Escondo el contenido forzando el collapse
                const bsCollapse = new bootstrap.Collapse(`#commentEdit${commentId}`, {
                    toggle: true,
                })

                // Evento propio de Bootstrap
                // Realiza acciones al esconder completamente el element del collapse
                editableComment.addEventListener('hidden.bs.collapse', event => {
                    commentContainer.removeChild(editableComment);
                    showElement(buttonList[i]);
                    showElement(originalComment);
                })

            })

            // TODO: Hacer que se muestre el contenido del comentario nuevo
            submitButton.addEventListener('click', (event) => {
                let commentText = commentTextarea.value;

                // Validaciones
                if(!commentText) {
                    commentErrorMessage.textContent = 'Por favor, ingrese un comentario';
                    return false;
                }
                if(commentText.length < 10) {
                    commentErrorMessage.textContent = 'Los comentarios deben de tener mínimo 10 caracteres';
                    return false;
                }

                if(commentText == originalComment.innerText) {
                    commentErrorMessage.textContent = 'El comentario no puede ser igual al original';
                    return false;
                }

                // Hago la solicitud
                requestCommentEdit(commentId, COMMENT_EDIT_ROUTE, commentText, submitButton);

                // // Escondo el contenido forzando el collapse
                // const bsCollapse = new bootstrap.Collapse(`#commentEdit${commentId}`, {
                //     toggle: true,
                // })
                //
                // editableComment.addEventListener('hidden.bs.collapse', event => {
                //     commentContainer.removeChild(editableComment);
                //     showElement(buttonList[i]);
                //     showElement(originalComment);
                // })
            })

        })
    }
});

/**
 * Actualiza el comentario con el nuevo contenido.
 *
 * Al actualizar el contenido, se esconderán los elementos
 * editables y se mostrará el nuevo comentario.
 *
 * @param   {int}   commentId   ID del comentario a crear
 * @returns {HTMLDivElement}   Elemento completo
 */
function updateCommentContent(commentId, status) {
    // Contenedor del comentario
    let commentContainer = document.getElementById(`commentContent${commentId}`);

    // Obtengo el comentario original
    let originalComment = commentContainer.getElementsByTagName('p')[0];

    // Guardo el nuevo comentario
    let editableComment = document.getElementById(`commentEdit${commentId}`);

    // Obtengo el botón para editar el comentario
    let editButton = document.getElementById(`#commentEditButton${commentId}`);

    let toastMessage = document.getElementById('toast-body__message');

    const bsCollapse = new bootstrap.Collapse(`#commentEdit${commentId}`, {
        toggle: true,
    })

    // Creo instancia para mostrar la notificacion
    const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toastNotification);

    if(status.includes('success')) {
        // Obtengo el nuevo comentario
        let newCommentText = (document.getElementById(`commentEditTextarea${commentId}`)).value;
        originalComment.textContent = newCommentText;

        toastMessage.textContent = 'Se ha editado el comentario exitosamente.';

    } else {
        toastMessage.textContent = 'Ha habido un error al editar el comentario.';
    }

    editableComment.addEventListener('hidden.bs.collapse', event => {
        commentContainer.removeChild(editableComment);
        showElement(editButton);
        showElement(originalComment);
    })

    toastBootstrap.show();
}


/**
 * Crea y devuelve un elemento Form con
 * los elementos necesarios para editar el comentario.
 *
 * @param   {int}   commentId   ID del comentario a crear
 * @returns {HTMLDivElement}   Elemento completo
 */
function createCommentElements(commentId) {

    // Elemento principal
    let commentForm = document.createElement('div');
    //commentForm.setAttribute('action', '#');
    commentForm.setAttribute('id', `commentEdit${commentId}`);
    commentForm.setAttribute('class', 'collapse');

    // Contenedor de textarea
    let containerTextarea = document.createElement('div');
    containerTextarea.setAttribute('class', 'd-flex mt-1 mb-2');

    // Contenedor de los botones
    let containerButtons = document.createElement('div');
    containerButtons.setAttribute('class', 'd-grid gap-md-2 gap-1 d-md-flex justify-content-md-end');

    //// ELEMENTOS

    // Contenido del comentario
    let textarea = document.createElement('textarea');
    textarea.setAttribute('type', 'submit');
    textarea.setAttribute('id', `commentEditTextarea${commentId}`);
    textarea.setAttribute('class', 'd-flex flex-row form-control');
    textarea.setAttribute('placeholder', 'Ingrese un nuevo comentario..');
    textarea.setAttribute('minlength', 10);
    textarea.setAttribute('maxlength', 255);
    textarea.setAttribute('required', true);

    let errorMessage = document.createElement('span');
    errorMessage.setAttribute('id', `commentEditError${commentId}`);
    errorMessage.setAttribute('class', 'd-flex text-danger');

    // Boton de envio
    let submitButton = document.createElement('button');
    submitButton.setAttribute('type', 'submit');
    submitButton.setAttribute('id', `commentEditSubmit${commentId}`);
    submitButton.setAttribute('class', 'btn btn-primary');
    submitButton.innerText = 'Editar';

    // Boton de salida
    let exitButton = document.createElement('button');
    exitButton.setAttribute('type', 'button');
    exitButton.setAttribute('id', `commentEditExit${commentId}`);
    exitButton.setAttribute('class', 'btn btn-light btn-outline-secondary');
    exitButton.innerText = 'Salir';

    // Asigno elementos a sus respectivos contenedores
    containerTextarea.append(textarea);
    containerButtons.append(submitButton);
    containerButtons.append(exitButton);

    // Agrego ambos elementos al formulario
    commentForm.append(containerTextarea);
    commentForm.append(errorMessage);
    commentForm.append(containerButtons);

    return commentForm;
}

/**
 * Esconde el elemento deseado agregando la clase d-none de
 * Bootstrap.
 *
 * @param   {HTMLP}   commentId   ID del comentario a crear
 * @returns {HTMLElement}   Elemento completo
 */
function hideElement(element) {
    if(!element.classList.contains('d-none'))
        element.classList.add('d-none');
}

/**
 * Muestra el elemento deseado removiendo la clase d-none de
 * Bootstrap.
 *
 * @param   {HTMLP}   commentId ID del comentario a crear
 * @returns {HTMLElement}       Elemento completo
 */
function showElement(element) {
    if(element.classList.contains('d-none'))
        element.classList.remove('d-none');
}


/**
 * Realiza una consulta AJAX para realizar la edición
 * de un comentario
 *
 * @param   {int}               id            ID del comentario a editar
 * @param   {string}            url           ID del comentario a editar
 * @param   {HTMLButtonElement} submitButton  ID del comentario a editar
 */
function requestCommentEdit(id, url, text, submitButton) {
    $.ajax({
        type: 'POST',
        url: url,
        data: {
            id: id,
            comment: text,
        },
        async: true,
        dataType: 'json',
        beforeSend: function() {
            submitButton.innerHTML = LOADING_BUTTON_CONTENT;
        },
        success: function (data) {
            updateCommentContent(id, data.status);
        },
        error: function(error) {
            alert('Ha habido un error con su comentario, intente más tarde o consulte con el administrador.');
        },
        complete: function() {
            submitButton.textContent = 'Enviar';
        }
    });
}

/**
 * Sin utilizar
 *
 * @param   {int}   commentId   ID del comentario a crear
 * @returns {HTMLDivElement}   Elemento completo
 */
function meGusta(id) {
    $.ajax({
        type: 'POST',
        url: POST_LIKE_ROUTE,
        data: { id: id },
        async: true,
        dataType: 'json',
        success: function(data) {
            console.log(data['likes']);
        }
    });
}
