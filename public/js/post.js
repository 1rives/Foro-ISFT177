const POST_LIKE_ROUTE = Routing.generate('likes');
const COMMENT_EDIT_ROUTE = Routing.generate('comment_edit');
const CURRENT_POST_ID = window.location.pathname.split('/').pop();
window.addEventListener("load", (event) => {
    // Utilizado para verificar que solamente se esté
    // editando un comentario por vez guardando el ID
    // del comentario, en el caso de que no se esté editando
    // ninguno, deberá estar en null.
    var currentCommentIdBeingEdited = null;

    // Obtengo los botones utilizados para editar comentarios
    let buttonList = document.querySelectorAll('button[id*="commentEditButton"]');
    let buttonListLength = buttonList.length;

    for (let i = 0; i < buttonListLength; i++ ) {
        buttonList[i].addEventListener( 'click', (event) => {
            // TODO: Cada vez que un usuario quiera editar un comentario sin cerrar otro
            // que tenga abierto, el anterior debera cerrarse automaticamente.
            //
            // TODO: Que el código se entienda =)
            //
            // if(currentCommentIdBeingEdited != null) {
            //     // Evento propio de Bootstrap
            //     // Realiza acciones al esconder completamente el element del collapse
            //     let activeElement = document.getElementById(`commentEdit${currentCommentIdBeingEdited}`);
            //
            //     // Escondo el elemento anteriormente activo
            //     const bsCollapse = new bootstrap.Collapse(activeElement, {
            //         toggle: true,
            //     })
            //
            //     activeElement.addEventListener('hidden.bs.collapse', (event) => {
            //         // Escondo los elementos
            //         //commentContainer.removeChild(activeElement);
            //
            //         //showElement(currentCommentBeingEdited);
            //         //hideElement(originalComment);
            //         currentCommentIdBeingEdited = null;
            //     })
            // }

            // ID del comentario actual
            let commentId = event.target.id.replace(/[^0-9]/g, '');

            // Contenedor del comentario
            let commentContainer = document.getElementById(`commentContent${commentId}`);
            // Nuevo comentario en estado de edición
            currentCommentIdBeingEdited = i;

            // Obtengo el comentario original
            let originalComment = commentContainer.getElementsByTagName('p')[0];

            // Si ya existe el elemento, no lo creo de nuevo
            let isElementAlreadyCreated = Boolean(document.getElementById(`commentEdit${commentId}`));

            if(!isElementAlreadyCreated) {
                let newCommentEdit = createCommentElements(commentId);
                commentContainer.appendChild(newCommentEdit);
            }

            // Escondo el comentario actual y muestro la edición
            hideElement(buttonList[i]);
            hideElement(originalComment);


            let editableComment = document.getElementById(`commentEdit${commentId}`);

            // Abro el collapse, mostrando todo el contenido
            const bsCollapse = new bootstrap.Collapse(`#commentEdit${commentId}`, {
                toggle: true,
            })

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

                    currentCommentIdBeingEdited = null;
                })

            })

        })
    }
});

/**
 * Crea y devuelve un elemento Form con
 * los elementos necesarios para editar comentario.
 *
 * @param   {int}   commentId   ID del comentario a crear
 * @returns {HTMLFormElement}   Elemento completo
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
    commentForm.append(containerButtons);

    return commentForm;
}

/**
 * Esconde el elemento deseado agregando la clase d-none de
 * Bootstrap.
 *
 * @param   {HTMLP}   commentId   ID del comentario a crear
 * @returns {HTMLFormElement}   Elemento completo
 */
function hideElement(element) {
    if(!element.classList.contains('d-none'))
        element.classList.add('d-none');
}

/**
 * Muestra el elemento deseado removiendo la clase d-none de
 * Bootstrap.
 *
 * @param   {HTMLP}   commentId   ID del comentario a crear
 * @returns {HTMLFormElement}   Elemento completo
 */
function showElement(element) {
    if(element.classList.contains('d-none'))
        element.classList.remove('d-none');
}

function toggleCommentEdit() {

}


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

function editComment(id, url) {
    $.ajax({
        type: 'POST',
        url: COMMENT_EDIT_ROUTE,
        data: {id: id},
        async: true,
        dataType: 'json',
        success: function (data) {
            console.log(data['comment']);
        }
    });
}