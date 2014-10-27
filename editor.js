var DDLEditor = new function(){
    this.xml = '';//currect xml code
    this.texts = [];//translates
    this.rows_counter = 1;
    this.cells_counter = 1;
 
    this.RenderEditor = function(){
        /*clipboard*/
        jQuery('#main_ddl_editor').before('<div id="gen_clipboard"></div>');
        jQuery('#gen_clipboard').html('<div class="clipboard"></div>');
        jQuery('#gen_clipboard .clipboard').html('<div class="cell cell1"></div>');
        jQuery('#gen_clipboard .clipboard .cell').html('<div class="placeholder"></div>');
        jQuery('#gen_clipboard .clipboard').append('<p>'+this.texts['< drag items to your clipboard']+'</p>');
        
        this.getXML();        
        
        /*main editor*/
        if(jQuery('#playground dd_layout').html()===''){
            jQuery('#main_ddl_editor .rows').html('<div data-selected="true" class="row-container" id="container_row_'+this.rows_counter+'"></div>');
            this.AddRow('row_'+this.rows_counter);
        }else{
            jQuery.each(jQuery('#playground dd_row'),function(ri, rval){
                
                var row_id = rval.id.replace('x_', '');
                
                jQuery('#main_ddl_editor .rows').append('<div data-selected="true" class="row-container" id="container_'+row_id+'"></div>');
                DDLEditor.AddRow(row_id);
                
                jQuery.each(jQuery('#playground #x_'+row_id+' dd_cell'),function(ci, cval){
                    if(jQuery(cval).attr('size')>0){
                        jQuery('#main_ddl_editor #'+cval.id.replace('x_', '')).removeClass('cell1').addClass('cell'+jQuery(cval).attr('size')).css('width','');
                    }
                });
            });
        }
        
        
    };
    
    this.getXML = function(){
        var xml_data = jQuery('#hidden_content').html();
        xml_data = xml_data.replace('&lt;','<', "gim");
        xml_data = xml_data.replace('&gt;','>', "gim");
        jQuery('#playground').html(xml_data);
    };
    
    this.setXML = function(){
        jQuery('#workshop').html(jQuery('#playground').html());
        jQuery.each(jQuery('#workshop dd_row'),function(ri, rval){
            var original_row_id = rval.id.replace('x_', '');
            var total_size = 0;
            jQuery.each(jQuery('#workshop #'+rval.id+' dd_cell'),function(ci, cval){
                var original_cell_id = cval.id.replace('x_', '');
                var size = Math.round((jQuery('#main_ddl_editor #'+original_row_id+' #'+original_cell_id).width()-50)/65)+1;
                total_size = total_size+size;
                if(total_size>12){jQuery(this).remove();}
            });
        });
        
        jQuery('#hidden_content').html(jQuery('#workshop').html().replace("\n\n","\n"));
    };
    
    this.AddRow = function (row_id){
        /*add cells*/
        jQuery('#main_ddl_editor #container_'+row_id).append('<div class="row" id="'+row_id+'"></div>');
        if(jQuery("#playground #x_" + row_id).length === 0) {
            jQuery('#playground dd_layout').append("\n"+'<dd_row name="'+row_id+'" id="x_'+row_id+'"></dd_row>'+"\n");
        }
        if(this.GetCellXMLOption('cell_'+this.cells_counter, 'name')===undefined){
            for (var i=0;i<12;i++){ 
                jQuery('#playground dd_layout #x_'+row_id).append("\n"+'<dd_cell name="'+Base64.encode('cell '+this.cells_counter)+'" id="x_cell_'+this.cells_counter+'"></dd_cell>');
                jQuery('#main_ddl_editor #'+row_id).append('<div class="cell cell1" data-size="1" id="cell_'+this.cells_counter+'"><div class="placeholder"><div class="cell-content">'+this.GetCellXMLOption('cell_'+this.cells_counter, 'name')+'</div></div></div>');
                this.cells_counter++;
            }
        }else{
            for (var i=0;i<12;i++){ 
                if(jQuery("#playground #x_cell_" + this.cells_counter).length !== 0) {
                    jQuery('#main_ddl_editor #'+row_id).append('<div class="cell cell1" data-size='+this.GetCellXMLOption('cell_'+this.cells_counter, 'size')+' id="cell_'+this.cells_counter+'"><div class="placeholder"><div class="cell-content">'+this.GetCellXMLOption('cell_'+this.cells_counter, 'name')+'</div></div></div>');
                }
                this.cells_counter++;
            }
        }
        
        /*add toolbar*/
        jQuery('#main_ddl_editor #container_'+row_id).append('<div class="row-toolbar"></div>');
        jQuery('#main_ddl_editor #container_'+row_id+' .row-toolbar').append('<div class="rows-actions" style="display:inline-block;"></div>');
        jQuery('#main_ddl_editor #container_'+row_id+' .row-toolbar .rows-actions').append('<h2 class="row-title">'+row_id+'</h2>');
        jQuery('#main_ddl_editor #container_'+row_id+' .row-toolbar .rows-actions').append('<button class="btn btn-edit">'+this.texts['Edit']+'</button>');
        jQuery('#main_ddl_editor #container_'+row_id+' .row-toolbar .rows-actions').append('<button class="btn btn-css">'+this.texts['CSS']+'</button>');
        jQuery('#main_ddl_editor #container_'+row_id+' .row-toolbar').append('<button class="btn btn-add-row add_new_row" id="add_below_'+row_id+'">'+this.texts['Add row below']+'</button>');
        
        jQuery('#main_ddl_editor #container_'+row_id).append('<div class="clear"></div>');
        this.rows_counter++;
        this.SetupEvents();
        this.setXML();
        return false;
    };
    
    this.AddRowBelow = function (row_id){
        jQuery('.add_new_row').hide();
        jQuery('#main_ddl_editor .rows #container_'+row_id).after('<div class="row-container" id="container_row_'+this.rows_counter+'"></div>');
        this.AddRow('row_'+this.rows_counter);
    };
    
    this.GetCellXMLOption = function (cell_id, option_name){
        var res = '';
        if(option_name==='content'){
            res = jQuery('#x_'+cell_id).html();
        }else{
            res = jQuery('#x_'+cell_id).attr(option_name);
        }
         
        if(typeof res !== "undefined" && option_name==='name'){
            res = Base64.decode(res);
        }
        if(typeof res !== "undefined" && option_name==='content'){
            res = Base64.decode(res);
        }
        return res;
    };
    
    this.SetCellXMLValue = function (cell_id, option_name, option_value){
        
        if(option_name!=='content'){
            if(option_name==='name'){
                jQuery('#'+cell_id+' .cell-content').html(option_value);
                option_value = Base64.encode(option_value);
            }
            jQuery('#x_'+cell_id).attr(option_name, option_value);
        }else{
            option_value = Base64.encode(option_value);
            jQuery('#x_'+cell_id).html(option_value);
        }
        this.setXML();
    };
       
    
    this.SetupEvents = function(){
        jQuery('#main_ddl_editor .btn.btn-edit').css({'display':'inline-block','opacity':'0'});
        jQuery('#main_ddl_editor .btn.btn-css').css({'display':'inline-block','opacity':'0'});
        
        jQuery('#main_ddl_editor .row-container').mouseover(function(){
            jQuery(this).attr('data-selected', 'true');
            jQuery(this).find('.rows-actions .btn').css('opacity','1');
        });
        jQuery('#main_ddl_editor .row-container').mouseout(function(){
            jQuery(this).attr('data-selected', '');
            jQuery(this).find('.rows-actions .btn').css('opacity','0');
        });
       
        jQuery('#main_ddl_editor .row-container .cell').mouseover(function(){
            jQuery(this).find('.placeholder').attr('data-selected', 'true');
            jQuery(this).find('.ui-resizable-handle').css('opacity','1');
            
        });
        jQuery('#main_ddl_editor .row-container .cell').mouseout(function(){
            jQuery(this).find('.placeholder').attr('data-selected', '');
            jQuery(this).find('.ui-resizable-handle').css('opacity','0');
        });
        
        
        jQuery('.add_new_row').click(function(){
            var bellow_to = this.id.replace('add_below_','');
            DDLEditor.AddRowBelow(bellow_to);
            return false;
        });
        
        jQuery( '#main_ddl_editor .row' ).sortable({
            stop: function( event, ui ) {
                DDLEditor.CheckSortableCell(event, ui);
            }
        });
        jQuery( '#main_ddl_editor .row' ).disableSelection();
        
        jQuery('#main_ddl_editor .cell').resizable({
            ghost: true,
            maxHeight: 50,
            minHeight: 50,
            maxWidth: 775,
            minWidth: 50,
            helper: "ui-resizable-helper",
            handles: "w,e" ,
            stop: function( event, ui ) {
                DDLEditor.CheckResizeCell(event, ui);
            }
        });
    };
    
    this.CheckSortableCell = function(event, ui){
        var current_id = ui.item.context.id;
        var new_current_cell = 0;
        jQuery.each(jQuery('#'+current_id).parent('.row').find('.cell'),function(ri, rval){
            if(rval.id === current_id){
                new_current_cell=ri-1;
            }
        });
        var xml_code = jQuery('#playground #x_'+current_id)[0].outerHTML;
        jQuery('#playground #x_'+current_id)[0].outerHTML = '';
        var prev = jQuery('#'+current_id).parent('.row').find('.cell')[new_current_cell];
        jQuery('#x_'+prev.id).after("\n"+xml_code);
        

        this.setXML();
        debugger;
        
    };
    
    this.CheckResizeCell = function (event, ui){
        var old_size = Math.round((ui.originalSize.width-50)/65)+1;
        var new_size = Math.round((ui.size.width-50)/65)+1;
        var current_id = ui.element.context.id;
        var temp_sizer = 0;

        if(new_size>old_size){
            //merge
            temp_sizer = new_size-old_size;
            if(ui.originalPosition.left===ui.position.left){
                //to right
                var temp_id = current_id;
                var cell_to_delete = [];
                for (var i=0;i<12;i++){ 
                    if(temp_sizer>0){
                        var current_size = parseInt(jQuery('#'+current_id).attr('data-size'));
                        var next_size = parseInt(jQuery('#'+temp_id).next().attr('data-size'));
                        temp_id = jQuery('#'+temp_id).next().attr('id');
                        if(typeof temp_id !== "undefined"){
                            temp_sizer=temp_sizer-next_size;
                            cell_to_delete.push(temp_id);
                            var size_to_set = current_size+next_size;
                            jQuery('#'+current_id).removeClass('cell'+current_size).addClass('cell'+size_to_set).css('width','').attr('data-size', size_to_set);
                            jQuery('#x_'+current_id).attr('size',size_to_set);
                        }
                    }
                }
                for (var i=0;i<cell_to_delete.length;i++){
                    jQuery('#main_ddl_editor #'+cell_to_delete[i]).remove();
                    jQuery('#x_'+cell_to_delete[i]).remove();
                } 
            }else{
                //to left
                var temp_id = current_id;
                var cell_to_delete = [];
                for (var i=0;i<12;i++){ 
                    if(temp_sizer>0){
                        var current_size = parseInt(jQuery('#'+current_id).attr('data-size'));
                        var next_size = parseInt(jQuery('#'+temp_id).next().attr('data-size'));
                        temp_id = jQuery('#'+temp_id).prev().attr('id');
                        if(typeof temp_id !== "undefined"){
                            temp_sizer=temp_sizer-next_size;
                            cell_to_delete.push(temp_id);
                            var size_to_set = current_size+next_size;
                            jQuery('#'+current_id).removeClass('cell'+current_size).addClass('cell'+size_to_set).css('width','').css('left','').attr('data-size', size_to_set);
                            jQuery('#x_'+current_id).attr('size',size_to_set);
                        }
                    }
                }
                for (var i=0;i<cell_to_delete.length;i++){
                    jQuery('#main_ddl_editor #'+cell_to_delete[i]).remove();
                    jQuery('#x_'+cell_to_delete[i]).remove();
                } 
            }
        }else{
            //split
            var temp_sizer = old_size-new_size;
            if(ui.originalPosition.left===ui.position.left){
                //from right
                var prev_size = jQuery('#'+current_id).attr('data-size');
                for (var i=1;i<=temp_sizer;i++){ 
                    var current_size = (parseInt(jQuery('#'+current_id).attr('data-size')));
                    var size_to_set = current_size-1;
                    jQuery('#'+current_id).removeClass('cell'+current_size).addClass('cell'+size_to_set).css('width','').attr('data-size', size_to_set);
                    jQuery('#x_'+current_id).attr('size',size_to_set);
                    var id_to_set = parseInt(current_id.replace('cell_',''))+parseInt(prev_size)-i;
                    jQuery('#playground dd_layout #x_'+current_id).after("\n"+'<dd_cell name="'+Base64.encode('cell '+id_to_set)+'" id="x_cell_'+id_to_set+'"></dd_cell>');
                    jQuery('#'+current_id).after('<div class="cell cell1" data-size="1" id="cell_'+id_to_set+'"><div class="placeholder"><div class="cell-content">'+this.GetCellXMLOption('cell_'+id_to_set, 'name')+'</div></div></div>');
                }
                
            }else{
                //from left
                var prev_size = jQuery('#'+current_id).attr('data-size');
                for (var i=1;i<=temp_sizer;i++){ 
                    var current_size = (parseInt(jQuery('#'+current_id).attr('data-size')));
                    var size_to_set = current_size-1;
                    jQuery('#'+current_id).removeClass('cell'+current_size).addClass('cell'+size_to_set).css('width','').attr('data-size', size_to_set);
                    jQuery('#x_'+current_id).attr('size',size_to_set);
                    var id_to_set = parseInt(current_id.replace('cell_',''))+parseInt(prev_size)+i;
                    jQuery('#playground dd_layout #x_'+current_id).after("\n"+'<dd_cell name="'+Base64.encode('cell '+id_to_set)+'" id="x_cell_'+id_to_set+'"></dd_cell>');
                    jQuery('#'+current_id).after('<div class="cell cell1" data-size="1" id="cell_'+id_to_set+'"><div class="placeholder"><div class="cell-content">'+this.GetCellXMLOption('cell_'+id_to_set, 'name')+'</div></div></div>');
                }
            }
        }
        this.SetupEvents();
        this.setXML();
    };
};
