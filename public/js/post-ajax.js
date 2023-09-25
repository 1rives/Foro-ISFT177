
const POST_LIKE_ROUTE = Routing.generate('likes');
const COMMENT_EDIT_ROUTE = Routing.generate('comment_edit');

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