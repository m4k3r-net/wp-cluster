!function(a,b){b["true"]=a,jQuery(document).ready(function(a){var b={};a("#global-javascript").AceEditor({setEditorContent:function(){var a=this.$elem.val();b=this.editor,this.editor.getSession().setValue(a)},onInit:function(){this.load()},onLoaded:function(){},onDestroy:function(){console.log(this.editor)}}),a("#global-javascript-form").submit(function(){var c=b.getValue();a("#global-javascript").val(c)})}),function(a){var b=function(b){a.extend(this,b),this.$elem=this.element,this.element=this.$elem.attr("id"),this.$container=this.container?a(this.container):this.$elem.parent(),this.contWd=this.$container.width(),this.loaded=!1,this.tinymce=!!window.tinymce,this.onInit&&this.onInit.call(this)};b.prototype={load:function(){if(this.loaded)return!1;var b=this;this.$elem.hide(),this.insertEditor(),this.editor=ace.edit(this.aceId),this.$editor=a("#"+this.aceId),this.setEditorProps(),this.setEditorContent(),this.containerResizable(),this.editor.on("change",function(){b.synchronize.apply(b)}),this.editor.resize(!0),this.loaded=!0,this.onLoaded&&this.onLoaded.call(this)},insertEditor:function(){a('<div id="'+this.aceId+'"></div>').css({left:0,top:0,bottom:0,right:0,zIndex:1}).insertAfter(this.$elem)},setEditorProps:function(){this.editor.setTheme("ace/theme/"+this.theme),this.editor.getSession().setMode("ace/mode/javascript"),this.editor.getSession().setUseWrapMode(!0),this.editor.getSession().setWrapLimitRange()},setEditorContent:function(){this.editor.getSession().setValue(this.$elem.val())},containerResizable:function(){var a=this;this.$container.resizable({handles:"s"}).css({position:"relative",height:this.defaultHt,minHeight:"400px"}).on("resize.aceEditorResize",function(){a.editor.resize(!0)})},synchronize:function(){var a=this.editor.getValue();this.$elem.val(a),this.tinymce&&tinyMCE.get(this.element)&&tinyMCE.get(this.element).setContent(a)},destroy:function(){return this.loaded?(this.$editor.remove(),this.editor.destroy(),this.$container.resizable("destroy").off("resize.aceEditorResize").css({height:""}),this.$elem.show(),this.loaded=!1,void(this.onDestroy&&this.onDestroy.apply(this,arguments))):!1}},a.fn.AceEditor=function(c,d){var c=c||null,e=a(this).data("AceEditor");if(e&&"string"==typeof c&&e[c])e[c](d||null);else{if(!e)return this.each(function(){var d=a.extend({element:a(this),aceId:"ace-editor",theme:"textmate",defaultHt:"600px",container:!1},c);a(this).data("AceEditor",new b(d))});a.error('Method "'+c+'" does not exist on AceEditor!')}}}(jQuery)}({},function(){return this}());