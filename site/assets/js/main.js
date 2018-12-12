//Sidebar

$(document).on('click', 'sidebar switcher', function() {
    $('dashboard').toggleClass('mobile-active-sidebar');
    $('dashboard').toggleClass('mobile-active-chat');
});

$(document).on('click', 'sidebar section toggle', function() {
    var listId = $(this).attr('listId');
    $('sidebar section list[listid="' + listId + '"]').toggleClass('active');
    $(this).toggleClass('active');
    $(this).parents('section').find('input[search], btn[create-room]').toggleClass('active');
});

// Chat Close

$(document).on('click', 'sidebar section list item delete', function(event){
    var chatId = $(this).parents('item').attr('chat-id');
    $(this).parents('item').remove();
    if ($('chat').attr('chat-id') == chatId) {
        $('chat overflow').removeAttr('none');
        $('chat window content').html('');
    }
    event.stopPropagation();
});


//Login


$(document).on('click', 'login #login btn', function() {
    $.post("api/", {
        action: 'login',
        username: $('#login input[name="username"]').val(),
        password: $('#login input[name="password"]').val(),
    }, function(data) {
        data = JSON.parse(data);
        if (data.error)
            Snackbar.show({
                text: data.error,
                pos: 'bottom-center'
            });
        else
            location.reload();
    });
});

//Register
var registerPhotoFrame;
$(document).on('change', 'login #register btn[photo] input[type="file"]', function() {
    var file = document.querySelector('login #register btn input[type="file"]').files[0];
    var reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = function () {
        registerPhotoFrame.croppie('bind', {
            url: reader.result
        });
    };
    reader.onerror = function (error) {
        Snackbar.show({
            text: error,
            pos: 'bottom-center'
        });
    };

    $('login #register input[type="text"], login #register input[type="password"]').css('display', 'none');
    var parentBtn = $(this).parents('btn[photo]');
    parentBtn.attr('register', '');
    parentBtn.html('Register');
    parentBtn.removeAttr('photo');
    $('login #register btn[back]').css('display', '');
    $('#registerUploadPhoto').css('display', '');
});

$(document).on('click', 'login #register btn[back]', function() {
    $('login #register input[type="text"], login #register input[type="password"]').css('display', '');
    var btn = $('login #register btn[register]');
    btn.attr('photo', '');
    btn.html('Upload Photo<input type="file" accept="image/*" />');
    btn.removeAttr('register');
    $('login #register btn[back]').css('display', 'none');
    $('#registerUploadPhoto').css('display', 'none');
});

$(document).on('click', 'login #register btn[register]', function() {
    registerPhotoFrame.croppie('result', {
        type: 'base64',
        size: 'original'
    }).then(function (base64) {
        $.post("api/", {
            action: 'register',
            first_name: $('#register input[name="first_name"]').val(),
            last_name: $('#register input[name="last_name"]').val(),
            username: $('#register input[name="username"]').val(),
            password: $('#register input[name="password"]').val(),
            photo: base64
        }, function(data) {
            data = JSON.parse(data);
            if (data.error)
                Snackbar.show({
                    text: data.error,
                    pos: 'bottom-center'
                });
            else
                location.reload();
        });
    });
});

//Log Out


$(document).on('click', 'menu .btns btn[action="signout"]', function() {
    $.post("api/", {
        action: 'setOffline',
    }, function(data) {
        data = JSON.parse(data);
        if (data.error)
            Snackbar.show({
                text: data.error,
                pos: 'bottom-center'
            });
        else
            location.reload();
    });
});

// User search

$(document).on('keyup', 'sidebar section input[search="people"]', function() {
    if ($(this).val()) {
        $.post("api/", {
            action: 'searchUser',
            q: $(this).val()
        }, function(data) {
            data = JSON.parse(data);
            var html = '';
            data.forEach(element => {
                html += '<item chat-id="' + element.id + '" type="user" class="' + (element.websocket > 0 ? 'online' : '') + '"><photo style="background-image: url(' + element.photo + ');"></photo><name>' + element.first_name + ' ' + element.last_name + '</name></item>';
            });
            $('sidebar section .searchList[user]').css('display', '');
            if (html)
                $('sidebar section .searchList[user]').html(html);
            else
                $('sidebar section .searchList[user]').html('<div class="noResults">No Results</div>');
        });
    } else {
        $('sidebar section .searchList[user]').css('display', 'none');
    }
});

// Message search

$(document).on('keyup', 'sidebar section input[search="message"]', function() {
    if ($(this).val()) {
        $.post("api/", {
            action: 'searchMsg',
            q: $(this).val()
        }, function(data) {
            data = JSON.parse(data);
            var html = '';
            data.forEach(element => {
                html += '<item chat-id="' + element.id + '" type="message" class="' + (element.websocket > 0 ? 'online' : '') + '"><photo style="background-image: url(' + element.photo + ');"></photo><message>' + element.message + '</message><date>' + element.date + '</date></item>';
            });
            $('sidebar section .searchList[message]').css('display', '');
            if (html)
                $('sidebar section .searchList[message]').html(html);
            else
                $('sidebar section .searchList[message]').html('<div class="noResults">No Results</div>');
        });
    } else {
        $('sidebar section .searchList[message]').css('display', 'none');
    }
});

$(document).on('focus', 'sidebar section input[search="people"]', function() {
    if ($('sidebar section .searchList[user]').html())
        $('sidebar section .searchList[user]').css('display', '');
});

$(document).on('focus', 'sidebar section input[search="message"]', function() {
    if ($('sidebar section .searchList[message]').html())
        $('sidebar section .searchList[message]').css('display', '');
});

$(document).on('blur', 'sidebar section input[search="people"]', function() {
    $('sidebar section .searchList[user]').css('display', 'none');
});

$(document).on('blur', 'sidebar section input[search="message"]', function() {
    $('sidebar section .searchList[message]').css('display', 'none');
});

// User Select Chat

$(document).on('click', 'sidebar section list item:not(sidebar section list item delete)', function() {
    loadChat($(this).attr('chat-id'), $(this).attr('type'), $(this).find('name').text());
    $('sidebar section input[search="people"]').val('');
    $(this).find('alert').remove();
});

$(document).on('mousedown', 'sidebar section .searchList[user] item', function() {
    loadChat($(this).attr('chat-id'), $(this).attr('type'), $(this).find('name').text());
    $('sidebar section input[search="people"]').val('');
    $(this).find('alert').remove();
});

$(document).on('mousedown', 'sidebar section .searchList[message] item', function() {
    loadChat($(this).attr('chat-id'), $(this).attr('type'), $(this).find('name').text());
    $('sidebar section input[search="message"]').val('');
    $(this).find('alert').remove();
});

function loadChat(id, type, name) {
    if (parseInt($(window).width()) <= 600) {
        $('dashboard').removeClass('mobile-active-sidebar');
        $('dashboard').addClass('mobile-active-chat');
    }

    $('chat window content').html('');
    $('chat input').val('');
    $('chat overflow').attr('none', '');
    $('chat header name').text(name);
    $('chat').attr('chat-id', id);
    $('chat').attr('type', type);

    $.post("api/", {
        action: 'loadMsgs',
        type: type,
        chatId: $('chat').attr('chat-id')
    }, function(data) {
        data = JSON.parse(data);
        if (type == 'user') {
            data.msgs.forEach(element => {
                var date = new Date(parseInt(element.timestamp) * 1000);
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'pm' : 'am';
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
                $('chat window content').append('<msg ' + (element.fromId == data.friend.id ? '' : 'me') + ' msg-id="' + element.id + '" ' + (parseInt(element.countDiscussion) > 0 ? 'class="discussion"' : '') + '>'+
                                                    '<photo style="background-image: url(' + decodeURIComponent(((element.fromId == data.friend.id ? data.friend.photo : data.me.photo)+'').replace(/\+/g, '%20')) + ')"></photo>' +
                                                    '<data ' + ((element.seen == 0 && element.to != data.me.id) ? 'style="background-color: #bcbfec;"' : '' ) + '>' +
                                                        '<message>' + decodeURIComponent((element.message+'').replace(/\+/g, '%20')) + '</message>' +
                                                        '<div class="extr_data">' +
                                                            '<sender>' + (element.fromId == data.friend.id ? decodeURIComponent((data.friend.first_name+'').replace(/\+/g, '%20')) : decodeURIComponent((data.me.first_name+'').replace(/\+/g, '%20'))) + '</sender>' +
                                                            '<date>' + strTime + '</date>' +
                                                        '</div>' +
                                                    '</data>' +
                                                    '<more></more>' +
                                                    '<msgpopup><delete></delete><forward></forward><comment>' + element.countDiscussion + '<i></i></comment></msgpopup>' +
                                                '</msg>');
            });
            var setSeenInChat = {'action': 'setSeenInChat', type: $('chat').attr('type'), chatId: data.me.id, to: [data.friend.websocket]};
            conn.send(JSON.stringify(setSeenInChat));
        } else if (type == 'room') {
            data.forEach(element => {
                var date = new Date(parseInt(element.timestamp) * 1000);
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'pm' : 'am';
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
                $('chat window content').append('<msg ' + element.me + ' msg-id="' + element.id + '" ' + (parseInt(element.countDiscussion) > 0 ? 'class="discussion"' : '') + '>'+
                                                    '<photo style="background-image: url(' + decodeURIComponent((element.photo+'').replace(/\+/g, '%20')) + ')"></photo>' +
                                                    '<data>' +
                                                        '<message>' + decodeURIComponent((element.message+'').replace(/\+/g, '%20')) + '</message>' +
                                                        '<div class="extr_data">' +
                                                            '<sender>' + decodeURIComponent((element.first_name+'').replace(/\+/g, '%20')) + '</sender>' +
                                                            '<date>' + strTime + '</date>' +
                                                        '</div>' +
                                                    '</data>' +
                                                    '<more></more>' +
                                                    '<msgpopup><delete></delete><forward></forward><comment>' + element.countDiscussion + '<i></i></comment></msgpopup>' +
                                                '</msg>');
            });
        }
        $('chat window content').scrollTop($('chat window content')[0].scrollHeight);
        $('chat window content msg data:not(chat window content msg[me] data)').css('background', '');
    });
}

// Chat

$(document).on('keypress', 'chat input', function(event) {
    if(event.keyCode == 13){
      $('chat send').click();
    }
});

$(document).on('click', 'chat send-translate, discussion send-translate', function() {
    $(this).toggleClass('active');
});

var msg;

$(document).on('click', 'chat send', function() {
    if ($('chat input').val()) {
        var type = $('chat').attr('type');
        var msg = $('chat input').val();
        var chatId = $('chat').attr('chat-id');
        if ($('chat send-translate').hasClass('active')) {
            $.get('https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=' + (msg), function(data){
                msg = '';
                $.each(data[0], function (key, element) {
                    msg += element[0];
                });
                sendMsg(msg, type, chatId);
            });
        } else
            sendMsg(msg, type, chatId);
    }
});

function sendMsg(msg, type, chatId) {
    $.post("api/", {
        action: 'sendMsg',
        msg: msg,
        type: type,
        chatId: chatId
    }, function(data) {
        // console.log(data);
        if (data)
            conn.send(data);

        data = JSON.parse(data);

        var strTime = timestampToStr();
        // var time = new Date();
        // strTime = time.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        $('chat window content').append('<msg me msg-id="' + data.msgId + '">'+
                                            '<photo style="' + $('menu #account #pic').attr('style') + '"></photo>' +
                                            '<data ' + (type == 'user' ? 'style="background-color: #bcbfec;"' : '') + '>' +
                                                '<message>' + msg + '</message>' +
                                                '<div class="extr_data">' +
                                                    '<sender>' + $('menu #account p').text() + '</sender>' +
                                                    '<date>' + strTime + '</date>' +
                                                '</div>' +
                                            '</data>' +'<more></more>' +
                                            '<msgpopup><delete></delete><forward></forward><comment>0<i></i></comment></msgpopup>' +
                                        '</msg>');
        $('chat input').val('');
        $('chat window content').scrollTop($('chat window content')[0].scrollHeight);

        if (type == 'user') {
            if (!$('sidebar section[people] list item[chat-id="' + $('chat').attr('chat-id') + '"]').length) { 
                $('sidebar section[people] list').prepend('<item chat-id="' + $('chat').attr('chat-id') + '" type="user" class="online"><delete></delete><photo style="background-image: url(' + decodeURIComponent((data.toUserData.photo+'').replace(/\+/g, '%20')) + ');"></photo><data><name>' + decodeURIComponent((data.toUserData.first_name+'').replace(/\+/g, '%20')) + ' ' + decodeURIComponent((data.toUserData.last_name+'').replace(/\+/g, '%20')) + '</name><msg>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</msg><date>' + strTime + '</date></data></item>');
            } else {
                $('sidebar section[people] list item[chat-id="' + $('chat').attr('chat-id') + '"] data msg').html(decodeURIComponent((data.msg+'').replace(/\+/g, '%20')));
                $('sidebar section[people] list item[chat-id="' + $('chat').attr('chat-id') + '"] data date').html(strTime);
                $('sidebar section[people] list item[chat-id="' + $('chat').attr('chat-id') + '"]').insertBefore('sidebar section[people] list item:first-of-type');
            }
        } else {
            $('sidebar section[rooms] list item[chat-id="' + $('chat').attr('chat-id') + '"] data msg').html(decodeURIComponent((data.msg+'').replace(/\+/g, '%20')));
            $('sidebar section[rooms] list item[chat-id="' + $('chat').attr('chat-id') + '"] data date').html(strTime);
            $('sidebar section[rooms] list item[chat-id="' + $('chat').attr('chat-id') + '"]').insertBefore('sidebar section[rooms] list item:first-of-type');
        }
    });
}

$(document).on('click', 'chat window content msg more', function() {
    $('chat window content msg more, chat window content msg msgpopup').removeClass('active');
    $(this).parents('msg').find('msgpopup').addClass('active');
    $(this).addClass('active');
});

$(document).on('click', 'chat window content msg more.active', function() {
    $(this).parents('msg').find('msgpopup').removeClass('active');
    $(this).removeClass('active');
});

$(document).on('click', 'chat window content msg msgpopup delete', function() {
    var that = this;
    $.post("api/", {
        action: 'removeMsg',
        msgId: $(this).parents('msg').attr('msg-id'),
        type: $('chat').attr('type'),
        chatId: $('chat').attr('chat-id')
    }, function(data) {
        if (data) {
            $(that).parents('msg').remove();
            if ($('discussion').attr('msg-id') == $(this).parents('msg').attr('msg-id')) {
                $('discussion').css({'top' : '', 'bottom' : '', 'left' : '', 'right' : '', 'display' : ''});
                $('discussion').removeAttr('msg-id');
            }
            conn.send(data);
            data = JSON.parse(data);

            if (data.lastMsg) {
                $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + $('chat').attr('chat-id') + '"] data msg').html(decodeURIComponent((data.lastMsg.message+'').replace(/\+/g, '%20')));
                $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + $('chat').attr('chat-id') + '"] data date').html(timestampToStr(decodeURIComponent((data.lastMsg.timestamp+'').replace(/\+/g, '%20'))));
            } else {
                $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + $('chat').attr('chat-id') + '"]').remove();
                $('chat window content').html('');
                $('chat input').val('');
                $('chat overflow').removeAttr('none');
                $('chat header name').text('');
                $('chat').attr('chat-id', '');
                $('chat').attr('type', '');
            }
        }
    });
});

$(document).on('click', 'chat window content msg msgpopup forward', function() {
    $(this).parents('msg').find('msgpopup').removeClass('active');
    $(this).parents('msg').find('more').removeClass('active');

    $('popup content input[name="peopleSearchForwarder"]').val('');
    $('popup[forwarder] content .searchList').html('');
    $('popup[forwarder]').addClass('active');
    $('popup[forwarder] title').text('Select Receiver');
    $('popup[forwarder]').attr('forward-msg-id', $(this).parents('msg').attr('msg-id'));
});


// Forwarder
$(document).on('keyup', 'popup content input[name="peopleSearchForwarder"]', function() {
    if ($(this).val()) {
        $.post("api/", {
            action: 'searchUser',
            q: $(this).val()
        }, function(data) {
            data = JSON.parse(data);
            var html = '';
            data.forEach(element => {
                html += '<item chat-id="' + element.id + '" type="user" class="' + (element.websocket > 0 ? 'online' : '') + '"><photo style="background-image: url(' + element.photo + ');"></photo><name>' + element.first_name + ' ' + element.last_name + '</name></item>';
            });
            $('popup content .searchList').css('display', '');
            if (html)
                $('popup content .searchList').html(html);
            else
                $('popup content .searchList').html('<div class="noResults">No Results</div>');
        });
    } else {
        $('popup content .searchList').css('display', 'none');
    }
});

$(document).on('focus', 'popup content input[name="peopleSearchForwarder"]', function() {
    if ($('popup content .searchList').html())
        $('popup content .searchList').css('display', '');
});

$(document).on('blur', 'popup content input[name="peopleSearchForwarder"]', function() {
    $('popup content .searchList').css('display', 'none');
});

$(document).on('click', 'popup[forwarder] close', function() {
    $('popup content input[name="peopleSearchForwarder"]').val('');
    $('popup[forwarder] content .searchList').html('');
    $('popup[forwarder]').removeClass('active');
});

$(document).on('mousedown', 'popup[forwarder] content .searchList item', function() {
    $('popup content input[name="peopleSearchForwarder"]').val('');
    $('popup[forwarder] content .searchList').html('');
    $('popup[forwarder]').removeClass('active');

    var that = this;
    $.post("api/", {
        action: 'sendMsg',
        forwardedMsgId: $('popup[forwarder]').attr('forward-msg-id'),
        type: $(this).attr('type'),
        chatId: $(this).attr('chat-id')
    }, function(data) {
        // console.log(data);
        if (data)
            conn.send(data);

        data = JSON.parse(data);

        var strTime = timestampToStr();

        if ($('chat').attr('chat-id') == data.toUserData.id) {
            $('chat window content').append('<msg me msg-id="' + data.msgId + '">'+
                                                '<photo style="' + $('menu #account #pic').attr('style') + '"></photo>' +
                                                '<data style="background-color: #bcbfec;">' +
                                                    '<message>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</message>' +
                                                    '<div class="extr_data">' +
                                                        '<sender>' + $('menu #account p').text() + '</sender>' +
                                                        '<date>' + strTime + '</date>' +
                                                    '</div>' +
                                                '</data>' +'<more></more>' +
                                                '<msgpopup><delete></delete><forward></forward><comment>0<i></i></comment></msgpopup>' +
                                            '</msg>');
            $('chat window content').scrollTop($('chat window content')[0].scrollHeight);
        }

        if (!$('sidebar section[people] list item[chat-id="' + $(that).attr('chat-id') + '"]').length) { 
            $('sidebar section[people] list').prepend('<item chat-id="' + $(that).attr('chat-id') + '" type="user" class="online"><delete></delete><photo style="background-image: url(' + decodeURIComponent((data.toUserData.photo+'').replace(/\+/g, '%20')) + ');"></photo><data><name>' + decodeURIComponent((data.toUserData.first_name+'').replace(/\+/g, '%20')) + ' ' + decodeURIComponent((data.toUserData.last_name+'').replace(/\+/g, '%20')) + '</name><msg>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</msg><date>' + strTime + '</date></data></item>');
        } else {
            $('sidebar section[people] list item[chat-id="' + $(that).attr('chat-id') + '"] data msg').html(decodeURIComponent((data.msg+'').replace(/\+/g, '%20')));
            $('sidebar section[people] list item[chat-id="' + $(that).attr('chat-id') + '"] data date').html(strTime);
            $('sidebar section[people] list item[chat-id="' + $(that).attr('chat-id') + '"]').insertBefore('sidebar section[people] list item:first-of-type');
        }
    });
});



// Create group
$(document).on('keyup', 'popup[room-settings] content input[name="peopleSearchRoomMember"]', function() {
    if ($(this).val()) {
        $.post("api/", {
            action: 'searchUser',
            q: $(this).val()
        }, function(data) {
            data = JSON.parse(data);
            var html = '';
            data.forEach(element => {
                html += '<item chat-id="' + element.id + '" type="user" class="' + (element.websocket > 0 ? 'online' : '') + '"><photo style="background-image: url(' + element.photo + ');"></photo><name>' + element.first_name + ' ' + element.last_name + '</name></item>';
            });
            $('popup[room-settings] content .searchList').css('display', '');
            if (html)
                $('popup[room-settings] content .searchList').html(html);
            else
                $('popup[room-settings] content .searchList').html('<div class="noResults">No Results</div>');
        });
    } else {
        $('popup[room-settings] content .searchList').css('display', 'none');
    }
});

$(document).on('focus', 'popup[room-settings] content input[name="peopleSearchRoomMember"]', function() {
    if ($('popup[room-settings] content .searchList').html())
        $('popup[room-settings] content .searchList').css('display', '');
});

$(document).on('blur', 'popup[room-settings] content input[name="peopleSearchRoomMember"]', function() {
    $('popup[room-settings] content .searchList').css('display', 'none');
});

$(document).on('click', 'sidebar section btn[create-room]', function() {
    $('popup[room-settings]').addClass('active');
});

$(document).on('click', 'popup[room-settings] close', function() {
    $('popup[room-settings] content #roomImg').css('background-image', '');
    $('popup[room-settings] content input[name="roomName"]').val('');
    $('popup[room-settings] content input[name="peopleSearchRoomMember"]').val('');
    $('popup[room-settings] content #roomMembers').html('');
    $('popup[room-settings]').removeClass('active');
});

$(document).on('click', 'popup[room-settings] close', function() {
    $('popup[room-settings] content input[name="peopleSearchRoomMember"]').val('');
    $('popup[room-settings] content .searchList').html('');
    $('popup[room-settings]').removeClass('active');
});

$(document).on('mousedown', 'popup[room-settings] content .searchList item', function() {
    // console.log();
    if (!$('popup[room-settings] content #roomMembers item[chat-id="' + $(this).attr('chat-id') + '"]').length)
        $('popup content #roomMembers').append('<item chat-id="' + $(this).attr('chat-id') + '"><photo style="' + $(this).find('photo').attr('style') + ';"></photo></item>');

    $('popup[room-settings] content input[name="peopleSearchRoomMember"]').val('');
    $('popup[room-settings] content .searchList').html('');
});

$(document).on('change', 'popup[room-settings] content #roomImg input[type="file"]', function() {
    var file = document.querySelector('popup[room-settings] content #roomImg input[type="file"]').files[0];
    var reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = function () {
        $('popup[room-settings] content #roomImg').css('background-image', 'url(' + reader.result + ')');
    };
});

$(document).on('click', 'popup[room-settings] content #roomMembers item', function() {
    $(this).remove();
});

$(document).on('click', 'popup[room-settings] content btn[create-room]', function() {
    var title = $('popup[room-settings] content input[name="roomName"]').val();
    var photo = $('popup[room-settings] content #roomImg').css('background-image').slice(5, -2);
    var members = [];
    $.each($('popup[room-settings] content #roomMembers item'), function() {
        members.push($(this).attr('chat-id'));
    });
    
    $.post("api/", {
        action: 'createRoom',
        title: title,
        photo: photo,
        members: JSON.stringify(members)
    }, function(data) {
        conn.send(data);
        data = JSON.parse(data);
        if (data.error) {
            Snackbar.show({
                text: data.error,
                pos: 'bottom-center'
            });
        } else if (data.action) {
            var date = new Date();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'pm' : 'am';
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
            $('sidebar section[rooms] list').prepend('<item chat-id="' + data.chatId + '" type="room">' +
                                                        '<delete></delete>' +
                                                        '<photo style="background-image: url(' + data.photo + ');"></photo>' +
                                                        '<data>' +
                                                            '<name>' + data.title + '</name>' +
                                                            '<msg>' + data.msg + '</msg>' +
                                                            '<date>' + strTime + '</date>' +
                                                        '</data>' +
                                                    '</item>');
            $('popup[room-settings] content #roomImg').css('background-image', '');
            $('popup[room-settings] content input[name="roomName"]').val('');
            $('popup[room-settings] content input[name="peopleSearchRoomMember"]').val('');
            $('popup[room-settings] content #roomMembers').html('');
            $('popup[room-settings]').removeClass('active');
        }
    });
});



// Discussion

$(document).on('click', 'chat window content msg msgpopup comment', function() {
    var top;
    var bottom;
    var left;
    var right;

    var msg = $(this).parents('msg').find('data');
    if (typeof $(this).parents('msg').attr('me') !== typeof undefined && $(this).parents('msg').attr('me') !== false) {
        right = ($(window).width() - msg.offset().left) - (150 + msg.width() / 2);
    } else {
        left = msg.offset().left - (150 - msg.width() / 2) + 5;
    }
    if (msg.offset().top > ($(window).height() - msg.offset().top - msg.height())) {
        // if top offset > bottom (show popup above the msg)
        bottom = ($(window).height() - msg.offset().top) + 5;
    }  else {
        top = msg.offset().top + msg.height() + 25;
    }
    $(this).parents('msgpopup').removeClass('active');
    $(this).parents('msg').find('more').removeClass('active');
    $('discussion').css('display', 'block');
    $('discussion').css({'top' : (top ? top : ''), 'bottom' : (bottom ? bottom : ''), 'right' : (right ? right : ''), 'left' : (left ? left : '')});
    $('discussion title').html(msg.find('message').text());
    $('discussion window content').html('');
    loadDiscussion($(this).parents('msg').attr('msg-id'));
});

$(document).bind('mouseup', function(e) {
    if(!$(e.target).is('discussion, discussion *:not(close), chat window content msg msgpopup comment')) {
        $('discussion').css({'top' : '', 'bottom' : '', 'left' : '', 'right' : '', 'display' : ''});
        $('discussion').removeAttr('msg-id');
    }
});

$(document).on('keypress', 'discussion input', function(event) {
    if(event.keyCode == 13){
      $('discussion send').click();
    }
});

var discussion;

$(document).on('click', 'discussion send', function() {
    if ($('discussion input').val()) {
        var discussion = $('discussion input').val();
        var msgId = $('discussion').attr('msg-id');
        if ($('discussion send-translate').hasClass('active')) {
            $.get('https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=' + (discussion), function(data){
                discussion = '';
                $.each(data[0], function (key, element) {
                    discussion += element[0];
                });
                sendDiscussion(discussion, msgId);
            });
        } else
            sendDiscussion(discussion, msgId);
    }
});

function sendDiscussion(discussion, msgId) {
    $.post("api/", {
        action: 'sendDiscussion',
        discussion: discussion,
        msgId: msgId
    }, function(data) {
        if (data)
            conn.send(data);

        data = JSON.parse(data);

        var strTime = timestampToStr();
        // var time = new Date();
        // strTime = time.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        $('discussion window content').append('<msg me>'+
                                            '<photo style="' + $('menu #account #pic').attr('style') + '"></photo>' +
                                            '<data>' +
                                                '<message>' + discussion + '</message>' +
                                                '<div class="extr_data">' +
                                                    '<sender>' + $('menu #account p').text() + '</sender>' +
                                                    '<date>' + strTime + '</date>' +
                                                '</div>' +
                                            '</data>' +
                                        '</msg>');
        $('discussion input').val('');
        $('chat window content msg[msg-id=' + msgId + '] msgpopup comment').html(parseInt($('chat window content msg[msg-id=' + msgId + '] msgpopup comment').text()) + 1 + "<i></i>");
        if (!$('chat window content msg[msg-id=' + msgId + ']').hasClass('discussion'))
            $('chat window content msg[msg-id=' + msgId + ']').addClass('discussion');
        $('discussion window content').scrollTop($('discussion window content')[0].scrollHeight);
    });
}

function loadDiscussion(msgId) {
    $('discussion').attr('msg-id', msgId);
    $.post("api/", {
        action: 'loadDiscussion',
        msgId: msgId
    }, function(data) {
        data = JSON.parse(data);
        data.forEach(element => {
            var date = new Date(parseInt(element.timestamp) * 1000);
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'pm' : 'am';
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
            $('discussion window content').append('<msg ' + (!element.isMe ? '' : 'me') + '>'+
                                                '<photo style="background-image: url(' + decodeURIComponent(((element.photo)+'').replace(/\+/g, '%20')) + ')"></photo>' +
                                                '<data>' +
                                                    '<message>' + decodeURIComponent((element.message+'').replace(/\+/g, '%20')) + '</message>' +
                                                    '<div class="extr_data">' +
                                                        '<sender>' + decodeURIComponent((element.first_name+'').replace(/\+/g, '%20')) + '</sender>' +
                                                        '<date>' + strTime + '</date>' +
                                                    '</div>' +
                                                '</data>' +
                                            '</msg>');
        });
        $('discussion window content').scrollTop($('discussion window content')[0].scrollHeight);
    });
}






















// FUNCTIONS

function timestampToStr(timestamp = null) {
    var date;
    if (timestamp)
        date = new Date(timestamp * 1000);
    else
        date = new Date();
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var ampm = hours >= 12 ? 'pm' : 'am';
    var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    return(hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear());
}