$(document).ready(function(){

var inProgress = false,
    count = 9;

    $(window).scroll(function() {

        if($(window).scrollTop() + $(window).height() >= $(document).height() - 100 && !inProgress && location.href === 'http://dev.zhirov.su/') {

            $.ajax({            
                url: '/',
                method: 'POST',
                data: {getimage: true, count: count},
                beforeSend: function() {
                    inProgress = true;
                }
            }).done(function(data){
                data = jQuery.parseJSON(data);
                if (data.length > 0) {
                    $.each(data, function(index, value){
                        $('.parse_image').append("<div class=\"work\"><a href="
                                + value.id + "><img src=\"../../upload/mini/"
                                + value.name + "\" class=\"media\" alt=\"\"/><div class=\"caption\"><div class=\"work_title\"><h1>"
                                + value.description + "</h1></div></div></a></div>");
                    });

                    inProgress = false;
                    count += 6;
            }});
        } else if($(window).scrollTop() + $(window).height() >= $(document).height() - 100 && !inProgress && location.href === 'http://dev.zhirov.su/mygallery') {

        $.ajax({            
            url: '/mygallery',
            method: 'POST',
            data: {getimage: true, count: count},
            beforeSend: function() {
                inProgress = true;
            }
        }).done(function(data){
            data = jQuery.parseJSON(data);
            if (data.length > 0) {
                $.each(data, function(index, value){
                    $('.parse_image').append("<div class=\"work\"><a href=\"/mygallery/"
                            + value.id + "\"><img src=\"../../upload/mini/"
                            + value.name + "\" class=\"media\" alt=\"\"/><div class=\"caption\"><div class=\"work_title\"><h1>"
                            + value.description + "</h1></div></div></a></div>");
                });
                
                inProgress = false;
                count += 6;
            }});
            
        }
    });
    
    $('.device').click(function() {
        
        var id = $(this).data('id'),
            userid = $(this).data('userid'),
            inProgress = true,
            element = $(this);
            
        if(inProgress)
        {
            $.ajax({            
                url: '/settings',
                method: 'POST',
                data: {deletesession: true, id: id, userid: userid},
                beforeSend: function() {
                    inProgress = true;
                }
            }).done(function(data){
                var isTrue = jQuery.parseJSON(data);
                if(isTrue === true)
                {
                    element.remove();
                    if(!$("span").is(".device"))
                    {
                        $('#container').remove();
                    }
                    
                    inProgress = false;
                }
            });
        }
    });
    
    $('#upload').on('click', function() {
        var file_data = $('#image').prop('files')[0],
            form_data = new FormData(),
            descript = $('#description').val();
        
        form_data.append('image', file_data);
        form_data.append('description', descript);
        form_data.append('submit', true);
        
        if(!inProgress){
            
            $('.content').append("<span class=\"loadImage\"><img src=\"../views/img/load.gif\" /> Загрузка изображения. Пожалуйста подождите.</span>");
        
            $.ajax({
                url: '/download',
                method: 'POST',
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'text',
                data: form_data,
                beforeSend: function() {
                    inProgress = true;
                }
            }).done(function(data){
                var out = jQuery.parseJSON(data);
                $('.loadImage').fadeOut(500, function(){$(this).remove();
                    if(out['result'] === 1)
                    {
                        $('#image').prop('value', null);
                        $('#description').val('');
                        $('.parse_image').append("<div class=\"work\"><a href="
                                + out['image']['id'] + "><img src=\"../../upload/mini/"
                                + out['image']['name'] + "\" class=\"media\" alt=\"\"/><div class=\"caption\"><div class=\"work_title\"><h1>"
                                + out['image']['description'] + "</h1></div></div></a></div>");
                                
                        $('.content').append("<span class=\"message\">" + out['message'] + "</span>");
                        $('.message').fadeOut(3000, function(){$(this).remove(); inProgress = false;});
                    }
                    else
                    {
                        $('.content').append("<span class=\"errors\">" + out['message'] + "</span>");
                        $('.errors').fadeOut(3000, function(){$(this).remove(); inProgress = false;});
                    }
                });
            });
        }
});
    
    
});