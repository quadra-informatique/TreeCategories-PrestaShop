$(document).ready(function() { 
  var toBeOpened = $("#categoryTree .openNode");
  var initially_opened = new Array();

  for(var i=0; i < toBeOpened.length; i++){
    initially_opened.push(toBeOpened[i].id);
  }

  $("#categoryTree").jstree({ 
    "ui" : {
       "initially_select" : initially_opened,
       "selected_parent_open" : true,
    },
    "plugins" : [ "themes", "html_data", "crrm", "dnd", "ui"],
  });

  $("#categoryTree a").live("click", function(e) {
    var href = $(this).attr("href");
    document.location = href;
  });

  $('#categoryTree').bind('move_node.jstree',function(event,data){
    var reg=new RegExp("(cat_id_)", "g");
    // new parent id
    //console.debug(data.args[0].np.children('a').attr('id').replace(reg,''));
    // moved category id
    //console.debug(data.args[0].o.children('a').attr('id').replace(reg,''));
    $.ajax({
       url: tree_base_folder+"moveCategory.php",
       data: 'parent='+data.args[0].np.children('a').attr('id').replace(reg,'')+'&target='+data.args[0].o.children('a').attr('id').replace(reg,''),
    }).done(function ( json ) { 
          // data is an array of errors
          var data = $.parseJSON(json);
          var content = '<div class=';
          if(data.length != 0){
            content += '"warn" style="display:block;"><ul style="line-height:20px">';
            for(var i=0; i < data.length; i++){
                content += '<li>'+data[i]+'</li>';
            }
          }
          else{
            content += '"conf" style="display:block;"><ul style="line-height:20px">';
            content += '<li>Success</li>';
         }
         content += '</ul></div>';
         $('#treeInfos').empty();
         $('#treeInfos').append(content);
       });
  });
}); 
