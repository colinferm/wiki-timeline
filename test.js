jQuery(document).ready(function(a){function d(){var c=a(this).attr("id").substring(14);a("#modal").css("display","block");a("#shade").css("display","block");var b=null;if(0!=c)for(var d=0;d<ursTimelineEvents.length;d++){var e=ursTimelineEvents[d];if(e.id==c){b=e;break}}else b={id:0,start_year:ursSelectedYear,end_year:ursSelectedYear,planets:[],categories:[]};b&&(a("#edit_id").val(b.id),a("#edit_start_year").val(b.start_year),a("#edit_end_year").val(b.end_year),a("#edit_title").val(b.title),a("#edit_caption").val(b.caption),
a("#edit_notes").val(b.notes),a("#edit_image").val(b.image_url),a("#edit_icon").val(b.icon_url),a("#edit_text_color").val(b.text_color),a("#edit_planets").importTags(b.planets.join(",")),a("#edit_categories").importTags(b.categories.join(",")))}function e(){a("#modal").css("display","none");a("#shade").css("display","none")}function h(){var c=a(this).attr("id").substring(16);a.ajax({url:wgScriptPath+"/index.php?action=ajax",type:"POST",data:{rs:"ursDeleteTimelineEvent",rsrnd:(new Date).getTime(),
rsargs:[{id:c}]},success:function(b){JSON.parse(b).status&&(a("#timeline-row-"+c).remove(),a("#timeline-spacer-"+c).remove())}})}a("#timeline_view_list").click(function(){a("#dynamic_timeline").css("display","none");a("#timeline_view_key").css("display","none");a("#timeline_view_list").css("display","none");a("div.printfooter").before(a("#list_timeline").text())});a.getJSON("/wiki/extensions/Timeline/planets.json",function(c){a("#edit_planets").tagsInput({autocomplete_url:c,autocomplete:{selectFirst:!1,
autoFill:!0,autoFocus:!0},width:"565px",height:"10px",defaultText:"Add a planet"})});a.getJSON("/wiki/api.php?action=query&format=json&list=allpages&aplimit=max&apnamespace=14&apfilterredir=nonredirects",function(c){c=c.query.allpages;var b=[];for(i=0;i<c.length;i++){var d={value:c[i].title.substring(9)};b.push(d)}a("#edit_categories").tagsInput({autocomplete_url:b,autocomplete:{selectFirst:!1,autoFill:!0,autoFocus:!0},width:"565px",height:"10px",defaultText:"Add a category"})});a("[id^='edit-timeline-']").click(d);
a("[id^='edit-timeline-']").css("cursor","pointer");a("#edit_save").click(function(){var c=a("#edit_id").val(),b=a("#edit_start_year").val(),g=a("#edit_end_year").val(),j=a("#edit_title").val(),f=a("#edit_caption").val(),l=a("#edit_notes").val(),m=a("#edit_image").val(),n=a("#edit_icon").val(),p=a("#edit_text_color").val(),q=a("#edit_categories").val(),r=a("#edit_planets").val(),k={id:c,start_year:b,end_year:g,title:j,caption:f,notes:l,image_url:m,icon_url:n,text_color:p,categories:q,planets:r};a.ajax({url:wgScriptPath+
"/index.php?action=ajax&rs=ursUpdateTimelineEvent&rsrnd="+(new Date).getTime(),type:"POST",data:{rs:"ursUpdateTimelineEvent",rsrnd:(new Date).getTime(),rsargs:[k]},success:function(b){b=JSON.parse(b);if(b.status){var e=b.event.title;0==b.event.title.length&&0<b.event.caption.length?e=b.event.caption:0==b.event.title.length&&0==b.event.caption.length&&(e=b.event.notes);if(0==c)c=b.event.id,k.id=c,l=b.event.notes,k.notes=l,b='<tr id="timeline-row-'+c+'"><td id="timeline-title-'+c+'">'+e+'</td><td id="edit-timeline-'+
c+'">Edit</td><td id="delete-timeline-'+c+'">Delete</td></tr><tr id="timeline-spacer-'+c+'"><td colspan="3">&nbsp;</td></tr>',a("#timeline-events").append(b),a("#edit-timeline-"+c).click(d),a("#delete-timeline-"+c).click(h),ursTimelineEvents.push(k);else{a("#timeline-title-"+b.event.id).html(e);for(var e=[],f=0;f<ursTimelineEvents.length;f++){var g=ursTimelineEvents[f];g.id==c?e.push(b.event):e.push(g)}ursTimelineEvents=e}}}});e()});a("#close").click(e);a("[id^='delete-timeline-']").css("cursor",
"pointer");a("[id^='delete-timeline-']").click(h)});
jQuery(window).load(function(){$("#dynamic_timeline").length?window.location.hash?timelineLoad(new Date(window.location.hash.substring(1))):timelineLoad(new Date("2085")):wgIsArticle&&0==$("#dynamic_timeline").length?($("div.entry-content").before('<div id="timeline-modal-shade" style="display:none;"></div><div id="timeline-modal" style="display:none;"><button id="timeline-modal-close">X</button><div id="dynamic_timeline" class="timeline-default" style="height: 600px; margin-top: 20px; margin-bottom: 50px;" data-planets="" data-categories=""></div></div>'),$("<link>").attr("rel",
"stylesheet").attr("type","text/css").attr("href",wgScriptPath+"/extensions/Timeline/timeline_ajax/styles/modal-graphics.css").appendTo("head"),$("div.entry-content a").click(function(){var a=$(this).attr("href"),d=a.indexOf("Timeline");$("#timeline-modal-close").click(hideTimelineModal);$("#timeline-modal-shade").click(hideTimelineModal);console.log("Timeline Index: "+d);$(document).keyup(function(a){27==a.keyCode&&"block"==$("#timeline-modal").css("display")&&hideTimelineModal()});if("Timeline"==
$(this).attr("title")&&0<d){a=a.substring(d+8+1);console.log("Timeline Year: "+a);$("#timeline-modal").css("display","block");$("#timeline-modal-shade").css("display","block");if($("#dynamic_timeline").length&&$("#dynamic_timeline").children().length){d=(new Date(a)).getFullYear()+2;d=new Date(""+d+"");dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(a));dynamicTimeline.getBand(0)._decorators[3]._startDate=new Date(a);dynamicTimeline.getBand(0)._decorators[3]._endDate=new Date(d);dynamicTimeline.getBand(1)._decorators[3]._startDate=
new Date(a);dynamicTimeline.getBand(1)._decorators[3]._endDate=new Date(d);dynamicTimeline.paint();try{_gaq.push(["_trackEvent","timeline","viewed",""+a+""])}catch(e){}}else{try{_gaq.push(["_trackEvent","timeline","loaded",""+a+""])}catch(h){}timelineLoad(new Date(a))}return!1}})):wgIsArticle&&$("#dynamic_timeline").length&&$("div.entry-content a").click(function(){var a=$(this).attr("href"),d=a.indexOf("Timeline");if("Timeline"==$(this).attr("title")&&0<d)return $("html, body").animate({scrollTop:$("#Timeline_of_Events").offset().top},
1E3),a=a.substring(d+8+1),console.log("Timeline Year: "+a),dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(a)),!1})});function hideTimelineModal(){console.log("Hiding Timeline Modal");$("#timeline-modal").css("display","none");$("#timeline-modal-shade").css("display","none")}var dynamicTimeline;
function timelineLoad(a){for(var d=new Timeline.DefaultEventSource,e=[Timeline.createBandInfo({eventSource:d,date:"Jan 01 2035 00:00:00 GMT",width:"85%",intervalUnit:SimileAjax.DateTime.YEAR,intervalPixels:100}),Timeline.createBandInfo({overview:!0,eventSource:d,date:"Jan 01 2035 00:00:00 GMT",width:"15%",intervalUnit:SimileAjax.DateTime.DECADE,intervalPixels:100})],h=0;h<e.length;h++){var c="#FFFFFF",b="transparent";0<h&&(b=c="transparent");e[h].decorators=[new Timeline.SpanHighlightDecorator({startDate:"2086",
endDate:"2088",color:"transparent",opacity:35,endLabel:"Age of Colonization"}),new Timeline.SpanHighlightDecorator({startDate:"2088",endDate:"2210",color:c,opacity:65,endLabel:"Age of War"}),new Timeline.SpanHighlightDecorator({startDate:"2210",endDate:"2360",color:b,opacity:35,endLabel:"Age of Betrayal"})];!j&&!g&&(c=a.getFullYear()+2,c=new Date(""+c+""),console.log("Adding Year decorator: "+c),e[h].decorators[3]=new Timeline.SpanHighlightDecorator({startDate:a,endDate:c,color:"#B2B2B2",opacity:65}))}e[1].syncWith=
0;e[1].highlight=!0;var g=$("#dynamic_timeline").data("planets"),j=$("#dynamic_timeline").data("categories"),f="/wiki/api.php?action=timeline&format=json";g.length&&(f+="&tlplanets="+g);j.length&&(f+="&tlcategories="+j);dynamicTimeline=Timeline.create(document.getElementById("dynamic_timeline"),e);a&&dynamicTimeline.getBand(0).setCenterVisibleDate(a);$.getJSON(f,function(a){d.loadJSON(a,f);(j.length||g.length)&&dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(a.events[1].start))})};