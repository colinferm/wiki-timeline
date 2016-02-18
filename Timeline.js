jQuery(document).ready(function($) {
	$("#timeline_view_list").click(function() {
		$("#dynamic_timeline").css('display', 'none');
		$("#timeline_view_key").css('display', 'none');
		$("#timeline_view_list").css('display', 'none');
		$("div.printfooter").before($("#list_timeline").text());
	});

	$.getJSON('/wiki/extensions/Timeline/planets.json', function(data) {
		$("#edit_planets").tagsInput({
			autocomplete_url: data,
			autocomplete: {
				selectFirst: false,
				autoFill: true,
				autoFocus: true
			},
			width: '565px',
			height: '10px',
			defaultText: 'Add a planet'
		});
	});

	$.getJSON('/wiki/api.php?action=query&format=json&list=allpages&aplimit=max&apnamespace=14&apfilterredir=nonredirects', function(data) {
		var cats = data.query.allpages;
		//console.log("Number of pages found: " + cats.length);
		var prefill = [];
		for (i = 0; i < cats.length; i++) {
			var cat = cats[i];
			var useable = cat.title.substring(9);
			//console.log("Found useable category: " + useable);
			var obj = { value: useable };
			prefill.push(obj);
		}
		$("#edit_categories").tagsInput({
			autocomplete_url: prefill,
			autocomplete: {
				selectFirst: false,
				autoFill: true,
				autoFocus: true
			},
			width: '565px',
			height: '10px',
			defaultText: 'Add a category'
		});
	});

	$("[id^='edit-timeline-']").click(editLinkHandler);
	$("[id^='edit-timeline-']").css('cursor', 'pointer');

	
	function editLinkHandler() {
		var id = $(this).attr('id').substring(14);
		$("#modal").css('display', 'block');
		$("#shade").css('display', 'block');
		var item = null;
		if (id != 0) {
			for (var i = 0; i < ursTimelineEvents.length; i++) {
				var event = ursTimelineEvents[i];
				if (event['id'] == id) {
					item = event;
					break;
				}
			}
		} else {
			item = {
				id: 0,
				start_year: ursSelectedYear,
				end_year: ursSelectedYear,
				show_filter_only: 0,
				planets: [],
				categories: []
			};
		}

		if (item) {
			$("#edit_id").val(item['id']);
			$("#edit_start_year").val(item['start_year']);
			$("#edit_end_year").val(item['end_year']);
			$("#edit_title").val(item['title']);
			$("#edit_caption").val(item['caption']);
			$("#edit_notes").val(item['notes']);
			$("#edit_image").val(item['image_url']);
			$("#edit_icon").val(item['icon_url']);

			if (item['show_filter_only'] == 1) $("#edit_private").attr('checked', 'checked');

			$("#edit_text_color").val(item['text_color']);
			$("#edit_planets").importTags(item['planets'].join(','));
			$("#edit_categories").importTags(item['categories'].join(','));
		}
	};
	
	$("#edit_save").click(function() {
		var id = $("#edit_id").val();
		var startYear = $("#edit_start_year").val();
		var endYear = $("#edit_end_year").val();
		var title = $("#edit_title").val();
		var caption = $("#edit_caption").val();
		var notes = $("#edit_notes").val();
		var imageURL = $("#edit_image").val();
		var iconURL = $("#edit_icon").val();
		var textColor = $("#edit_text_color").val();
		var showFilteredOnly = 0;
		if ($("#edit_private").attr('checked')) {
			showFilteredOnly = 1;
		}
		var categories = $("#edit_categories").val();
		var planets = $("#edit_planets").val();
		var eventObj = {
				id: id,
				start_year: startYear,
				end_year: endYear,
				title: title,
				caption: caption,
				notes: notes,
				image_url: imageURL,
				icon_url: iconURL,
				text_color: textColor,
				show_filter_only: showFilteredOnly,
				categories: categories,
				planets: planets
				}

		$.ajax({
			//url: '/reference/Special:Timeline',
			//url: mw.util.wikiScript('Timeline'),
			url: wgScriptPath + "/index.php?action=ajax&rs=ursUpdateTimelineEvent&rsrnd=" + new Date().getTime(),
			type: 'POST',
			data: {
				rs: 'ursUpdateTimelineEvent',
				rsrnd: new Date().getTime(),
				rsargs: [
					eventObj
				]
			},
			success: function(data) {
				var reply = JSON.parse(data);
				//alert(reply.status);
				if (reply.status) {
					var titleText = reply.event.title;
					if (reply.event.title.length == 0 && reply.event.caption.length > 0) {
						titleText = reply.event.caption;
					} else if (reply.event.title.length == 0 && reply.event.caption.length == 0) {
						titleText = reply.event.notes;
					}

					if (id == 0) {
						id = reply.event.id;
						eventObj.id = id;

						notes = reply.event.notes;
						eventObj.notes = notes;

						var html = '<tr id="timeline-row-'+id+'">' +
						'<td id="timeline-title-'+id+'">'+titleText+'</td><td id="edit-timeline-'+id+'">Edit</td><td id="delete-timeline-'+id+'">Delete</td>' +
						'</tr>' +
						'<tr id="timeline-spacer-'+id+'"><td colspan="3">&nbsp;</td></tr>';

						$("#timeline-events").append(html);
						$("#edit-timeline-"+id).click(editLinkHandler);
						$("#delete-timeline-"+id).click(deleteLinkHandler);
						ursTimelineEvents.push(eventObj);
					} else {
						$("#timeline-title-"+reply.event.id).html(titleText);
						var timelineEvents = [];
						for (var i = 0; i < ursTimelineEvents.length; i++) {
							var event = ursTimelineEvents[i];
							if (event.id == id) {
								timelineEvents.push(reply.event);
							} else {
								timelineEvents.push(event);
							}
						}
						ursTimelineEvents = timelineEvents;
					}
				}
			}
		});

		hideTimelineEditModal();
	});

	$("#close").click(hideTimelineEditModal);

	function hideTimelineEditModal() {
		$("#modal").css('display', 'none');
		$("#shade").css('display', 'none');
	}

	$("[id^='delete-timeline-']").css('cursor', 'pointer');
	$("[id^='delete-timeline-']").click(deleteLinkHandler);
	function deleteLinkHandler() {
		var id = $(this).attr('id').substring(16);
	
		$.ajax({
			url: wgScriptPath + "/index.php?action=ajax",
			type: 'POST',
			data: {
				rs: 'ursDeleteTimelineEvent',
				rsrnd: new Date().getTime(),
				rsargs: [
					{
						id: id
					}
				]
			},
			success: function(data) {
				var reply = JSON.parse(data);
				//alert(reply.status);
				if (reply.status) {
					$("#timeline-row-"+id).remove();
					$("#timeline-spacer-"+id).remove();
				}
			}
		});
	};

});


jQuery(window).load(function() {
	var timeline = "Timeline";
	if ($("#dynamic_timeline").length) {
		console.log("Loading timeline " + window.location.hash.substring(1));
		if (window.location.hash) {
			timelineLoad(new Date(window.location.hash.substring(1)));
		} else {
			timelineLoad(new Date('2085'));
		}

	} 
	if (wgIsArticle && $("#dynamic_timeline").length == 0) {
		 $("div.entry-content").before('<div id="timeline-modal-shade" style="display:none;"></div><div id="timeline-modal" style="display:none;"><button id="timeline-modal-close">X</button><div id="dynamic_timeline" class="timeline-default" style="height: 600px; margin-top: 20px; margin-bottom: 50px;" data-planets="" data-categories=""></div></div>');
		$('<link>').attr('rel','stylesheet')
			.attr('type','text/css')
			.attr('href',wgScriptPath+'/extensions/Timeline/timeline_ajax/styles/modal-graphics.css')
			.appendTo('head');

		$("div.entry-content a").click(function() {
			var href = $(this).attr('href');
			var timelineIndex = href.indexOf(timeline);
			$("#timeline-modal-close").click(hideTimelineModal);
			$("#timeline-modal-shade").click(hideTimelineModal);
			console.log("Timeline Index: " + timelineIndex);

			$(document).keyup(function(e) {
				if (e.keyCode == 27 && $("#timeline-modal").css('display') == "block") {
					hideTimelineModal();
				}
			});

			if ($(this).attr('title') == "Timeline" && timelineIndex > 0) {
				var year = href.substring(timelineIndex + timeline.length + 1);
				console.log("Timeline Year: " + year);
				$("#timeline-modal").css('display', 'block');
				$("#timeline-modal-shade").css('display', 'block');

				if ($("#dynamic_timeline").length && $("#dynamic_timeline").children().length) {
					var tmpYear = (new Date(year)).getFullYear() + 2;
					var nextYear = new Date(""+tmpYear+"");
					//console.log("Data already loaded, showing new date");
					dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(year));
					dynamicTimeline.getBand(0)._decorators[3]._startDate = new Date(year);
					dynamicTimeline.getBand(0)._decorators[3]._endDate = new Date(nextYear);
					dynamicTimeline.getBand(1)._decorators[3]._startDate = new Date(year);
					dynamicTimeline.getBand(1)._decorators[3]._endDate = new Date(nextYear);
					dynamicTimeline.paint();

					//if (_gaq != undefined) {
					try {
						_gaq.push(['_trackEvent', 'timeline', 'viewed', ""+year+""]);
					} catch(e) {}
                                        //}
				} else {
					//console.log("No data loaded yet, loading timeline");
					//if (_gaq != undefined) {
					try {
						_gaq.push(['_trackEvent', 'timeline', 'loaded', ""+year+""]);
					} catch(e) {}
                                        //}
					timelineLoad(new Date(year));
				}

				return false;

			}
		});

	} else if (wgIsArticle && $("#dynamic_timeline").length > 0) {
		$("div.entry-content a").click(function() {
			var href = $(this).attr('href');
			var timelineIndex = href.indexOf(timeline);

			if ($(this).attr('title') == timeline && timelineIndex > 0) {
				$('html, body').animate({
					scrollTop: $("#dynamic_timeline").offset().top-20
				}, 1000);
				var year = href.substring(timelineIndex + timeline.length + 1);
				console.log("Timeline Year: " + year);
				dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(year));

				return false;
			}
			return true;
		});
	}
});

function hideTimelineModal() {
	console.log("Hiding Timeline Modal");
	$("#timeline-modal").css('display', 'none');
	$("#timeline-modal-shade").css('display', 'none');
}

var dynamicTimeline;
function timelineLoad(year) {

	var planets = $("#dynamic_timeline").data('planets');
	var categories = $("#dynamic_timeline").data('categories');

	var fetchURL = "/wiki/api.php?action=timeline&format=json";
	if (planets.length) fetchURL += "&tlplanets=" + planets;
	if (categories.length) fetchURL += "&tlcategories=" + categories;

	$.getJSON(fetchURL, function(data) {
		var events = data.events;
		var eventLen = events.length;
		var mainHotZones = [];
		var overHotZones = [];
		var hotZoneNum = 0;
		var hotZoneEvents = 0;
		var currYear = 0;
		var endYear = 0;
		for (var i = 0; i < eventLen; i++) {
			var event = events[i];
			var startYear = parseInt(event.start.substring(0,4));
			if (startYear != currYear) {
				if (hotZoneEvents >= 3) {
					console.log("Adding hotzone for years " + currYear + "-" + endYear + ", number of events: " + hotZoneEvents);
					mainHotZones[hotZoneNum] = {
						start: currYear,
						end: endYear,
						magnify: 5,
						unit: SimileAjax.DateTime.MONTH
					};
					overHotZones[hotZoneNum] = {
						start: currYear,
						end: endYear,
						magnify: 35,
						unit: SimileAjax.DateTime.MONTH
					};
					hotZoneNum++;
				}
				currYear = startYear;
				endYear = currYear + 1;
				hotZoneEvents = 0;
			}
			if (event.durrationEvent || event.start.length > 4) {
				//console.log("Found Hot Zone Event for year " + startYear + ", title: " + event.title);
				if (event.durrationEvent) {
					var end = parseInt(event.end.substring(0,4));
					if (end < (currYear + 3)) {
						hotZoneEvents++;
						if (end != currYear) endYear = end + 1;
					}
				} else {
					hotZoneEvents++;
				}
			}
		}
		console.log("Number of Hot Zones: " + mainHotZones.length);

		var eventSource = new Timeline.DefaultEventSource();
		var bandsInfo = [
			Timeline.createHotZoneBandInfo({
				zones: mainHotZones,
				eventSource: eventSource,
				date: "Jan 01 2035 00:00:00 GMT",
				width: "85%",
				intervalUnit: SimileAjax.DateTime.YEAR,
				intervalPixels: 100
			}),
			Timeline.createHotZoneBandInfo({
				zones: overHotZones,
				overview: true,
				eventSource: eventSource,
				date: "Jan 01 2035 00:00:00 GMT",
				width: "15%",
				intervalUnit: SimileAjax.DateTime.DECADE,
				intervalPixels: 100
			})
		];

		for (var i = 0; i < bandsInfo.length; i++) {
			var warColor = "#FFFFFF";
			var betColor = "transparent";
			if (i > 0) {
				warColor = "transparent";
				betColor = "transparent";
			}
			bandsInfo[i].decorators = [
				new Timeline.SpanHighlightDecorator({
					startDate:  "2086",
					endDate: "2088",
					color: "transparent",
					opacity: 35,
					endLabel: "Age of Colonization"
				}),
				new Timeline.SpanHighlightDecorator({
					startDate:  "2088",
					endDate: "2210",
					color: warColor,
					opacity: 65,
					endLabel: "Age of War"
				}),
				new Timeline.SpanHighlightDecorator({
					startDate:  "2210",
					endDate: "2360",
					color: betColor,
					opacity: 35,
					endLabel: "Age of Betrayal"
				})

			];
			if (!categories && !planets) {
				var tmpYear = year.getFullYear() + 2;
				var nextYear = new Date(""+tmpYear+"");
				console.log("Adding Year decorator: " + nextYear);
				bandsInfo[i].decorators[3] = new Timeline.SpanHighlightDecorator({
					startDate:  year,
					endDate: nextYear,
					color: "#B2B2B2",
					opacity: 65
				})
			}
		}
	
		bandsInfo[1].syncWith = 0;
		bandsInfo[1].highlight = true;

		dynamicTimeline = Timeline.create(document.getElementById('dynamic_timeline'), bandsInfo);
		if (year) dynamicTimeline.getBand(0).setCenterVisibleDate(year);

		eventSource.loadJSON(data, fetchURL);
		if (categories.length || planets.length) dynamicTimeline.getBand(0).setCenterVisibleDate(new Date(data.events[0].start));
	});
}

