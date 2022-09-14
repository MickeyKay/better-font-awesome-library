(function(d){var c=Object.values(bfa_vars.fa_icons);function b(e){return c.find(function(f){return f.title==e;});}function a(e){var f=e.style?' style="'+e.style+'"':"";
return'[icon name="'+e.slug+'"'+f+' class="" unprefixed_class=""]';}d(function(){d("body").on("mousedown",".bfa-iconpicker",function(g){g.preventDefault();
var f=d(this);f.iconpicker({placement:"bottomLeft",hideOnSelect:true,animation:false,selectedCustomClass:"selected",icons:c,fullClassFormatter:function(h){var e=[];
var i=b(h);return i.base_class;},});f.find(".iconpicker-item").each(function(){var e=d(this);var h=e.attr("title").replace(".","");e.attr("title",h);});
f.find(".iconpicker-search").trigger("focus");});d(".bfa-iconpicker").on("iconpickerSelected",function(h){var f=h.iconpickerItem.title.replace(".","");
var g=b(f);wp.media.editor.insert(a(g));});});})(jQuery);