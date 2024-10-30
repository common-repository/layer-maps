jQuery(document).ready(function($){

	var enableClustering = false;
	var map = $('#layermaps_map');
	var container = $('.layermaps_list_container');

	var layermaps_map;
	var layermaps_markers = [];
	var mapClusterer;
	var ajaxUrl;
	var pluginUrl;

	var allMarkers = [];

	if(typeof(layermaps_options) !== 'undefined') {
		enableClustering = layermaps_options.enable_clustering;
	}

	if(typeof(layermaps_params) !== 'undefined') {
		ajaxUrl = layermaps_params.ajax_url;
	}

	if(typeof(layermaps_params) !== 'undefined') {
		pluginUrl = layermaps_params.plugin_url;
	}

	$(container).on("click", "a.layer-label", function() {
		$(this).parent().find('ul').slideToggle(200);
		return false;
	});

	$(container).on("click", "a.pin-label", function() {

		var elem = $(this);

		var dataLayer = elem.attr('data-layer');
		var dataMarker = elem.attr('data-marker');
		var marker = allMarkers[dataLayer][dataMarker];

		if($(document).scrollTop() > map.offset().top - 50) {
			$('html, body').animate({
				scrollTop: map.offset().top - 50
			}, 600, function() {
				google.maps.event.trigger(marker, 'click');
			});
		}
		else {
			google.maps.event.trigger(marker, 'click');
		}

		return false;
	});

	if (map.length){
		var map_id = map.attr("data-mapid");

		$.ajax({
			type: "POST",
			url: ajaxUrl,
			data: { action: 'layermaps_get_pins', map_id: map_id },
			dataType: 'json',
			error: function(error) {
				console.log(error);
			},
			success: function(response) {
				initMap(response);
			}
		});
	}

	function updateTable(layers) {

		layers = (typeof layers !== 'undefined') ? layers : [];

		$('.layermaps_list_container ul.layers').empty();

		if(layers.length > 0) {
			$(layers).each(function(key, value) {

				var markers = layers[key][3];
				var layerColour = layers[key][2];

				var markersCount = markers.length;

				$('.layermaps_list_container ul.layers').append('<li class="layer layer-' + key + '"><a href="#" class="layer-label"><span>' + value[1] + '</span> (' + markersCount + ')</a><ul class="pins"></ul></li>');

				$('.layermaps_list_container ul.layers li.layer-' + key + ' ul.pins').empty();

				$(markers).each(function(marker_key, marker_value) {

					var pin_colour;

					if (layerColour !== '' && layerColour !== 'none') {
						pin_colour = layerColour;
					} else {
						pin_colour = marker_value[4];
					}

					var icon = pluginUrl + '/includes/pin.php?colour=' + pin_colour + '&number=' + (marker_key+1);

					var layer = '.layermaps_list_container ul.layers li.layer-' + key + ' ul.pins';
					$(layer).append('<li class="pin"><a href="#" data-layer="' + key + '" data-marker="' + marker_key + '" class="pin-label"><img src="' + icon + '"><span>' + marker_value[0] + '</span><div style="clear:both;"></div></a></li>');
				});

			});
		}

	}

	// Google Maps API

	function initMap(data) {
		layermaps_map = new google.maps.Map(document.getElementById('layermaps_map'), {
			center: { lat: 53.740399, lng: -1.593669 },
			zoom: 6,
			minZoom: 4,
			mapTypeControlOptions: {
				mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.TERRAIN, google.maps.MapTypeId.HYBRID],
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
			}
		});

		var layers = data[0];
		var kml = data[1];

		updateTable(layers);

		window.ctaLayer = new google.maps.KmlLayer(kml.url, {
			preserveViewport: true,
			suppressInfoWindows: true
		});
		window.ctaLayer.setMap(null);

		for (var j = 0; j < layers.length; j++) {
			var markers = layers[j][3];
			var layer_colour = layers[j][2];

			allMarkers[j] = [];

			for (var i = 0; i < markers.length; i++) {

				var company = markers[i];
				var title = company[0];

				if (layer_colour !== '' && layer_colour !== 'none') {
					var pin_colour = layer_colour;
				} else {
					var pin_colour = company[4];
				}
				var the_content = company[5];

				var icon = pluginUrl + '/includes/pin.php?colour=' + pin_colour + '&number=' + (i+1);

				var image = {
					url: icon,
					size: new google.maps.Size(32, 37),
					origin: new google.maps.Point(0, 0),
					scaledSize: new google.maps.Size(32, 37),
					anchor: new google.maps.Point(11, 30)
				};
				var Latlng = new google.maps.LatLng(company[1],company[2]);

				if (isNaN(company[1]) === false && isNaN(company[2]) === false) {
					var marker = new google.maps.Marker({
						map: layermaps_map,
						position: Latlng,
						icon: image,
						title: title,
						content: '<p><strong>' + title + '</strong></p><p>' + the_content + '</p>'
						//animation: google.maps.Animation.BOUNCE
					});

					layermaps_markers.push(marker);
				}

				var infowindow = new google.maps.InfoWindow({
					pixelOffset: new google.maps.Size(-1, 0)
				});
				google.maps.event.addListener(marker, 'click', (function(marker) {
					return function() {

						if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
							// Do nothing
						} else {
							document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
						}

						//layermaps_map.panTo(marker.getPosition());
						layermaps_map.setCenter(marker.getPosition());

						if(layermaps_map.getZoom() < 10) {
							layermaps_map.setZoom(10);
						}

						infowindow.setContent(this.content);
						infowindow.open(layermaps_map, marker);
					}
				})(marker));
				google.maps.event.addListener(layermaps_map, 'click', (function(layermaps_map) {
					return function() {
						if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
							// Do nothing
						} else {
							document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
						}
					}
				})(layermaps_map));
				google.maps.event.addListener(layermaps_map, 'dragend', (function(layermaps_map) {
					return function() {
						if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
							// Do nothing
						} else {
							document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
						}
					}
				})(layermaps_map));

				if (isNaN(company[1]) === false && isNaN(company[2]) === false) {
					allMarkers[j][i] = marker;
				}


			}
		}

		google.maps.event.addListenerOnce(layermaps_map, 'idle', function(){
			if(enableClustering) {
				var mcOptions = {gridSize: 15, maxZoom: 14};
				mapClusterer = new MarkerClusterer(layermaps_map, layermaps_markers, mcOptions);

				google.maps.event.addListener(mapClusterer, 'clusterclick', function(cluster){
					layermaps_map.setCenter(cluster.getCenter());
					layermaps_map.setZoom(layermaps_map.getZoom()+1);
				});
			}
		});

		// Create the DIV to hold the control and call the CenterControl() constructor
		// passing in this DIV.
		var centerControlDiv = document.createElement('div');
		centerControlDiv.className = 'filter-container';
		var centerControl = new CenterControl(centerControlDiv, layermaps_map, layers, kml);

		centerControlDiv.index = 1;
		layermaps_map.controls[google.maps.ControlPosition.TOP_RIGHT].push(centerControlDiv);
	}

	function filterMap(data) {
		if (data !== null && data.length > 0) {
			var layers = data[0];

			updateTable(data[0]);

			for (var j = 0; j < layers.length; j++) {
				var markers = layers[j][3];
				var layer_colour = layers[j][2];

				allMarkers[j] = [];

				for (var i = 0; i < markers.length; i++) {
					var company = markers[i];
					var title = company[0];
					if (layer_colour !== '' && layer_colour !== 'none') {
						var pin_colour = layer_colour;
					} else {
						var pin_colour = company[4];
					}
					var the_content = company[5];

					var icon = pluginUrl + '/includes/pin.php?colour=' + pin_colour + '&number=' + (i+1);

					var image = {
						url: icon,
						size: new google.maps.Size(32, 37),
						origin: new google.maps.Point(0, 0),
						scaledSize: new google.maps.Size(32, 37),
						anchor: new google.maps.Point(11, 30)
					};
					var Latlng = new google.maps.LatLng(company[1],company[2]);
					if (isNaN(company[1]) === false && isNaN(company[2]) === false) {
						var marker = new google.maps.Marker({
							map: layermaps_map,
							position: Latlng,
							icon: image,
							title: title,
							content: '<p><strong>' + title + '</strong></p><p>' + the_content + '</p>'
							//animation: google.maps.Animation.BOUNCE
						});
						layermaps_markers.push(marker);
					}
					var infowindow = new google.maps.InfoWindow({
						pixelOffset: new google.maps.Size(-1, 0)
					});
					google.maps.event.addListener(marker, 'click', (function(marker) {
						return function() {
							if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
								// Do nothing
							} else {
								document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
							}

							if(layermaps_map.getZoom() < 10) {
								layermaps_map.setZoom(10);
							}

							//layermaps_map.panTo(marker.getPosition());
							layermaps_map.setCenter(marker.getPosition());

							infowindow.setContent(this.content);
							infowindow.open(layermaps_map, this);
						}
					})(marker));
					google.maps.event.addListener(layermaps_map, 'click', (function(layermaps_map) {
						return function() {
							if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
								// Do nothing
							} else {
								document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
							}
						}
					})(layermaps_map));
					google.maps.event.addListener(layermaps_map, 'dragend', (function(layermaps_map) {
						return function() {
							if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
								// Do nothing
							} else {
								document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
							}
						}
					})(layermaps_map));

					if (isNaN(company[1]) === false && isNaN(company[2]) === false) {
						allMarkers[j][i] = marker;
					}
				}
			}

			if(enableClustering) {
				var mcOptions = {gridSize: 15, maxZoom: 14};
				mapClusterer = new MarkerClusterer(layermaps_map, layermaps_markers, mcOptions);

				google.maps.event.addListener(mapClusterer, 'clusterclick', function(cluster){
					layermaps_map.setCenter(cluster.getCenter());
					layermaps_map.setZoom(layermaps_map.getZoom()+1);
				});
			}

		}
		else {
			updateTable();
		}
	}

	function kmlFilter(data, kml_on) {

		var kml = {
			url: data
		};

		if (kml_on == 'yes') {
			window.ctaLayer = new google.maps.KmlLayer(kml.url, {
				preserveViewport: true,
				suppressInfoWindows: true
			});
			window.ctaLayer.setMap(layermaps_map);
		} else if (kml_on == 'no') {
			window.ctaLayer.setMap(null);
		}
	}

	function CenterControl(controlDiv, map, layers, kml) {

		// Set CSS for the filter control border.
		var controlUI = document.createElement('div');
		controlUI.title = 'Filter Layers';
		controlUI.id = 'layermaps_filter';
		controlUI.className = 'filter-control';
		controlDiv.appendChild(controlUI);

		// Set CSS for the filter control interior.
		var controlText = document.createElement('div');
		controlText.style.color = 'rgb(25,25,25)';
		controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
		controlText.style.fontSize = '11px';
		controlText.style.paddingLeft = '6px';
		controlText.style.paddingRight = '6px';
		controlText.style.paddingTop = '6px';
		controlText.style.paddingBottom = '6px';
		controlText.style.fontWeight = '500';
		controlText.innerHTML = '<span style="float:left">Filter Layers</span> <img src="https://maps.gstatic.com/mapfiles/arrow-down.png" draggable="false" style="display:inline; -webkit-user-select: none; border: 0px; padding: 0px; margin: -2px 0px 0px; position: relative; right: 0; top: 50%; width: 7px; height: 4px; margin-left: 25px;">';
		controlUI.appendChild(controlText);

		// Setting up each layer filter from the WP database
		var controlDropdown = document.createElement('div');
		controlDropdown.className = 'filter-dropdown hidden';
		controlDropdown.id = 'layermaps_filter_dropdown';
		controlDiv.appendChild(controlDropdown);

		var clickEvents = [];
		for (var i = 0; i < layers.length; i++) {
			var controlDropdownItem = window['controlDropdownItem'+layers[i][0]];
			controlDropdownItem = document.createElement('div');
			controlDropdownItem.id = 'layermaps_layerfilter_' + layers[i][0];
			controlDropdownItem.className = 'layermaps_layerfilter checked';
			controlDropdownItem.innerHTML = '<div class="checkbox" id="_layermaps_filter_' + layers[i][0] + '" data-layer-id="' + layers[i][0] + '"></div><span>' + layers[i][1] + '</span>';
			controlDropdown.appendChild(controlDropdownItem);

			clickEvents.push(controlDropdownItem);
		}

		// Adding KML layer filter
		if (kml !== '') {
			var controlKML = document.createElement('div');
			controlKML.id = 'layermaps_layerfilter_kml';
			controlKML.className = 'layermaps_layerfilter';
			controlKML.before = '<hr/>';
			controlKML.innerHTML = '<div class="checkbox" id="_layermaps_filter_kml" data-layer-id="kml"></div><span>KML Layer</span>';
			controlDropdown.appendChild(controlKML);
		}

		// Setting up click events for each layer
		for (var j = 0; j < clickEvents.length; j++) {
			clickEvents[j].addEventListener('click', function() {
				if (hasClass(document.getElementById(this['id']), 'checked') === true) {
					document.getElementById(this['id']).className = 'layermaps_layerfilter';
					deleteMarkers();
				} else {
					document.getElementById(this['id']).className = 'layermaps_layerfilter checked';
					deleteMarkers();
				}

				var layers = [];
				jQuery('.layermaps_layerfilter').each(function(){
					if (jQuery(this).hasClass("checked")) {
						layers.push(jQuery(this).attr('id').substring(22));
					}
				});

				var map = jQuery('#layermaps_map');
				var map_id = map.attr("data-mapid");

				map.addClass('filtering');

				jQuery.ajax({
					type: "POST",
					url: ajaxUrl,
					data: { action: 'layermaps_filter_pins', map_id: map_id, layers: layers },
					dataType: 'json',
					error: function(error) {
						console.log(error);
					},
					success: function(response) {
						filterMap(response);
						jQuery('#layermaps_map').removeClass('filtering');
					}
				});
			});
		}

		// Setting up click event for filter button
		controlUI.addEventListener('click', function() {
			if (hasClass(document.getElementById('layermaps_filter_dropdown'), 'hidden') === true) {
				document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown';
			} else {
				document.getElementById('layermaps_filter_dropdown').className = 'filter-dropdown hidden';
			}
		});

		// Setting up button and click event for kml layer option
		if (kml !== '') {
			controlKML.addEventListener('click', function() {
				if (hasClass(document.getElementById(this['id']), 'checked') === true) {
					document.getElementById(this['id']).className = 'layermaps_layerfilter';
					var kml_on = 'no';
				} else {
					document.getElementById(this['id']).className = 'layermaps_layerfilter checked';
					var kml_on = 'yes';
				}

				var map = jQuery('#layermaps_map');
				var map_id = map.attr("data-mapid");

				map.addClass('filtering');

				jQuery.ajax({
					type: "POST",
					url: ajaxUrl,
					data: { action: 'layermaps_kml_layer', map_id: map_id, kml_on: kml_on },
					dataType: 'json',
					error: function(error) {
						console.log(error);
					},
					success: function(response) {
						kmlFilter(response, kml_on);
						jQuery('#layermaps_map').removeClass('filtering');
					}
				});
			});
		}
	}

	function hasClass(element, cls) {
		return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
	}

	function setMapOnAll(map) {
		for (var i = 0; i < layermaps_markers.length; i++) {
			layermaps_markers[i].setMap(map);
		}
	}

// Removes the markers from the map, but keeps them in the array.
	function clearMarkers() {
		setMapOnAll(null);
	}

// Shows any markers currently in the array.
	function showMarkers() {
		setMapOnAll(layermaps_map);
	}

// Deletes all markers in the array by removing references to them.
	function deleteMarkers() {
		clearMarkers();
		if(enableClustering) {
			mapClusterer.clearMarkers();
		}
		layermaps_markers = [];
	}

});