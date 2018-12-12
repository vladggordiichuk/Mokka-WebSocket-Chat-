<?php
    session_start();
    require_once 'inc/config.php';
    require_once 'inc/db.php';
    date_default_timezone_set('Europe/Kiev');
?>

<html>
    <head>
        <title>Mokka</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/assets/css/main.css">
        <link rel="stylesheet" href="/assets/plugins/snackbar/dist/snackbar.min.css">
        <link rel="stylesheet" href="/assets/plugins/croppie/croppie.css">
        <script type="text/javascript" src="/assets/js/jquery.min.js"></script>
        <script type="text/javascript" src="/assets/plugins/snackbar/dist/snackbar.min.js"></script>
        <script type="text/javascript" src="/assets/plugins/croppie/croppie.min.js"></script>
        <script type="text/javascript" src="/assets/js/main.js"></script>
    </head>
    <body>
        <?php
        
        if (isset($_SESSION['userData']) && $_SESSION['userData']) {
            include 'tmpl/dashboard.php';
        } else {
            include 'tmpl/login.php';
        }
        
        ?>
        <script>
        var conn = new WebSocket('ws://localhost:8777');
        conn.onopen = function(e) {
            console.log("Connection established!");
        };

        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            if (data.action == 'setOnline') {
                $.post("api/", {
                    action: 'setOnline',
                    websocket: data.websocket
                });
            } else if (data.action == 'msg') {
                var date = new Date();
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'pm' : 'am';
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
                if (data.type == 'user') {
                    if (!$('sidebar section[people] list item[chat-id="' + data.from.id + '"]').length) { 
                        $('sidebar section[people] list').prepend('<item chat-id="' + data.from.id + '" type="user" class="online"><delete></delete><alert>1</alert><photo style="background-image: url(' + decodeURIComponent((data.from.photo+'').replace(/\+/g, '%20')) + ');"></photo><data><name>' + decodeURIComponent((data.from.first_name+'').replace(/\+/g, '%20')) + ' ' + decodeURIComponent((data.from.last_name+'').replace(/\+/g, '%20')) + '</name><msg>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</msg><date>' + strTime + '</date></data></item>');
                    } else {
                        $('sidebar section[people] list item[chat-id="' + data.from.id + '"] data msg').html(decodeURIComponent((data.msg+'').replace(/\+/g, '%20')));
                        $('sidebar section[people] list item[chat-id="' + data.from.id + '"] data date').html(strTime);
                        $('sidebar section[people] list item[chat-id="' + data.from.id + '"]').insertBefore('sidebar section[people] list item:first-of-type');
                        if ($('sidebar section[people] list item[chat-id="' + data.from.id + '"] alert').length) {
                            var alert = $('sidebar section[people] list item[chat-id="' + data.from.id + '"] alert').text();
                            if (alert != '99+') {
                                var newAlert = (parseInt(alert) + 1) < 100 ? (parseInt(alert) + 1) : '99+';
                                $('sidebar section[people] list item[chat-id="' + data.from.id + '"] alert').text(newAlert);
                            }
                        }
                    }
                    if ($('chat').attr('chat-id') == data.from.id) {
                        $('chat window content').append('<msg msg-id="' + data.msgId + '">'+
                                                        '<photo style="background-image: url(' + decodeURIComponent((data.from.photo+'').replace(/\+/g, '%20')) + ')"></photo>' +
                                                        '<data>' +
                                                            '<message>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</message>' +
                                                            '<div class="extr_data">' +
                                                                '<sender>' + decodeURIComponent((data.from.first_name+'').replace(/\+/g, '%20')) + '</sender>' +
                                                                '<date>' + strTime + '</date>' +
                                                            '</div>' +
                                                        '</data>' +
                                                        '<more></more>' +
                                                        '<msgpopup><delete></delete><forward></forward><comment>0<i></i></comment></msgpopup>' +
                                                    '</msg>');
                        var setSeenInChat = {'action': 'setSeenInChat', type: data.type, chatId: data.toUserData.id, to: [data.from.websocket]};
                        conn.send(JSON.stringify(setSeenInChat));
                        $('chat window content').scrollTop($('chat window content')[0].scrollHeight);
                        $('sidebar section[people] list item[chat-id="' + data.from.id + '"] alert').remove();
                        $.post("api/", {
                            action: 'setSeen',
                            chatId: $('chat').attr('chat-id')
                        });
                    } else {
                        if (!$('sidebar section[people] list item[chat-id="' + data.from.id + '"] alert').length)
                            $('<alert>1</alert>').insertAfter('sidebar section[people] list item[chat-id="' + data.from.id + '"] delete');
                    }
                } else if (data.type == 'room') {
                    if (!$('sidebar section[rooms] list item[chat-id="' + data.chatId + '"]').length) { 
                        $('sidebar section[rooms] list').prepend('<item chat-id="' + data.chatId + '" type="room"><delete></delete><photo style="background-image: url(' + data.photo + ');"></photo><data><name>' + data.title + '</name><msg>' + data.msg + '</msg><date>' + strTime + '</date></data></item>');
                    } else {
                        $('sidebar section[rooms] list item[chat-id="' + data.chatId + '"] data msg').html(decodeURIComponent((data.msg+'').replace(/\+/g, '%20')));
                        $('sidebar section[rooms] list item[chat-id="' + data.chatId + '"] data date').html(strTime);
                        $('sidebar section[rooms] list item[chat-id="' + data.chatId + '"]').insertBefore('sidebar section[rooms] list item:first-of-type');
                    }
                    if ($('chat').attr('chat-id') == data.chatId) {
                        $('chat window content').append('<msg msg-id="' + data.msgId + '">'+
                                                        '<photo style="background-image: url(' + decodeURIComponent((data.from.photo+'').replace(/\+/g, '%20')) + ')"></photo>' +
                                                        '<data>' +
                                                            '<message>' + decodeURIComponent((data.msg+'').replace(/\+/g, '%20')) + '</message>' +
                                                            '<div class="extr_data">' +
                                                                '<sender>' + decodeURIComponent((data.from.first_name+'').replace(/\+/g, '%20')) + '</sender>' +
                                                                '<date>' + strTime + '</date>' +
                                                            '</div>' +
                                                        '</data>' +
                                                        '<more></more>' +
                                                        '<msgpopup><delete></delete><forward></forward><comment>0<i></i></comment></msgpopup>' +
                                                    '</msg>');
                        $('chat window content').scrollTop($('chat window content')[0].scrollHeight);
                    }
                }
            } else if (data.action == 'discussion') {
                var date = new Date();
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'pm' : 'am';
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                var strTime = hours + ':' + minutes + ampm + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
                if ($('chat window content msg[msg-id=' + data.msgId + ']').length) {
                    $('discussion window content').append('<msg>'+
                                                        '<photo style="background-image: url(' + data.userImg + ')"></photo>' +
                                                        '<data>' +
                                                            '<message>' + data.msg + '</message>' +
                                                            '<div class="extr_data">' +
                                                                '<sender>' + data.userFirstName + '</sender>' +
                                                                '<date>' + strTime + '</date>' +
                                                            '</div>' +
                                                        '</data>' +
                                                    '</msg>');
                    $('chat window content msg[msg-id=' + data.msgId + '] msgpopup comment').html(parseInt($('chat window content msg[msg-id=' + data.msgId + '] msgpopup comment').text()) + 1 + "<i></i>");
                    if (!$('chat window content msg[msg-id=' + data.msgId + ']').hasClass('discussion'))
                        $('chat window content msg[msg-id=' + data.msgId + ']').addClass('discussion');
                    $('discussion window content').scrollTop($('discussion window content')[0].scrollHeight);
                }
            } else if (data.action == 'setSeenInChat') {
                if (data.type == 'user') {
                    if ($('chat').attr('chat-id') == data.chatId) {
                        $('chat window content msg data').css({'background': ''});
                    }
                }
            } else if (data.action == 'removeMsg') {
                $('chat window content msg[msg-id="' + data.msgId + '"]').remove();
                if ($('discussion').attr('msg-id') == data.msgId) {
                    $('discussion').css({'top' : '', 'bottom' : '', 'left' : '', 'right' : '', 'display' : ''});
                    $('discussion').removeAttr('msg-id');
                }

                if (data.lastMsg) {
                    $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + data.fromId + '"] data msg').html(decodeURIComponent((data.lastMsg.message+'').replace(/\+/g, '%20')));
                    $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + data.fromId + '"] data date').html(timestampToStr(decodeURIComponent((data.lastMsg.timestamp+'').replace(/\+/g, '%20'))));
                } else {
                    $('sidebar section[' + (data.type == 'user' ? 'people' : 'rooms') + '] list item[chat-id="' + data.fromId + '"]').remove();
                    $('chat window content').html('');
                    $('chat input').val('');
                    $('chat overflow').removeAttr('none');
                    $('chat header name').text('');
                    $('chat').attr('chat-id', '');
                    $('chat').attr('type', '');
                }

                if (data.type == 'user') {
                    if (data.unseen > 1)
                        $('sidebar section list item[chat-id="' + data.fromId + '"][type="' + data.type + '"] alert').html(data.unseen);
                    else
                        $('sidebar section list item[chat-id="' + data.fromId + '"][type="' + data.type + '"] alert').remove();
                }
            }
        }
        </script>
    </body>
</html>