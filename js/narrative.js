var link_width = 1.8;
var link_gap = 2;

var node_width = 10; // Set to panel_width later
var color = d3.scale.category10();
var raw_chart_width = 1000;

// Height of empty gaps between groups
// (Sparse groups and group ordering already
// provide a lot of whitespace though.)
var group_gap = 0;

// This is used for more than just text height.
var text_height = 8;

// If a name's x is smaller than this value * chart width,
// the name appears at the start of the chart, as
// opposed to appearing right before the first scene
// (the name doesn't make any sense).
var per_width = 0.3;

// The character's name appears before its first
// scene's x value by this many pixels
var name_shift = 10;

// True: Use a white background for names
var name_bg = true;

// True: Disregard actual scene duration and make
// all the scenes equal.
var equal_scenes = false;

// Between 0 and 1.
var curvature = 0.5;

// Scene width in panel_width
// i.e. scene width = panel_width*sw_panels
var sw_panels = 3;

// Longest name in pixels to make space at the beginning
// of the chart. Can calculate but this works okay.
var longest_name = 115;

// True: When deciding on which group to put a scene in,
// if there's a tie, break the tie based on which
// groups the scenes from which the links are incoming
// are in
// False: Arbitrary
//var tie_breaker = false;
// Set for each comic separately

// d3 function
function get_path(link) {
    var x0 = link.x0;
    var x1 = link.x1;
    var xi = d3.interpolateNumber(x0, x1);
    var x2 = xi(curvature);
    var x3 = xi(1 - curvature);
    var y0 = link.y0;
    var y1 = link.y1;

    return "M" + x0 + "," + y0
        + "C" + x2 + "," + y0
        + " " + x3 + "," + y1
        + " " + x1 + "," + y1;
} // get_path


function Character_(name, id, group) {
    this.name = name;
    this.id = id;
    this.group = group;
    this.group_ptr = null;
    this.first_scene = null;
    this.group_positions = {};
    this.group_name_positions = {};
} // Character_


function Link(from, to, group, char_id) {
    // to and from are ids of scenes
    this.from = from;
    this.to = to;
    this.char_id = char_id;
    this.group = group; // Group number
    this.x0 = 0;
    this.y0 = -1;
    this.x1 = 0;
    this.y1 = -1;
    this.char_ptr = null; // TODO: Not used
} // Link


function SceneNode(chars, start, duration, id) {
    this.chars = chars; // List of characters in the Scene (ids)
    this.start = start; // Scene starts after this many panels
    this.duration = duration; // Scene lasts for this many panels
    this.id = id;

    this.char_ptrs = [];
    // Determined later
    this.x = 0;
    this.y = 0;

    this.width = node_width; // Same for all nodes
    this.height = 0; // Will be set later; proportional to link count

    this.in_links = [];
    this.out_links = [];

    this.name = "";

    this.has_char = function(id) {
	for (var i = 0; i < this.chars.length; i++) {
	    if (id == this.chars[i])
		return true;
	}
	return false;
    }
    this.char_node = false;
    this.first_scene = null; // Only defined for char_node true
    // Used when determining the y position of the name (i.e. the char_node)
    // Char nodes are divided into x-regions, and the names in each region
    // are sorted separately. This is the index of the x-region.
    // ... Actually, I'll just keep an array of the nodes in every region.
    //this.x_region = 0;

    this.median_group = null;
    this.comic_name;

} // SceneNode


function reposition_node_links(scene_id, x, y, width, height, svg, ydisp, comic_name) {
    //console.log(d3.selectAll("[from=\"" + scene_id + "\"]"));
    var counter = 0;
    d3.selectAll("[to=\"" + comic_name + "_" +  scene_id + "\"]")
	.each(function(d) {
	    d.x1 =  x + width/2;
	    d.y1 -= ydisp;
	    counter += 1;
	})
	.attr("d", function(d) { return get_path(d); });

    counter = 0;
    d3.selectAll("[from=\"" + comic_name + "_" +  scene_id + "\"]")
	.each(function(d) {
	    d.x0 =  x + width/2;
	    d.y0 -= ydisp;
	    counter += 1;
	})
	.attr("d", function(d) { return get_path(d); });
} // reposition_link_nodes


function generate_links(chars, scenes) {
    var links = [];
    for (var i = 0; i < chars.length; i++) {
	// The scenes in which the character appears
	var char_scenes = [];
	for (var j = 0; j < scenes.length; j++) {
	    if (scenes[j].has_char(chars[i].id)) {
		char_scenes[char_scenes.length] = scenes[j];
	    } // if
	} // for


	char_scenes.sort(function(a, b) { return a.start - b.start; });
	chars[i].first_scene = char_scenes[0];
	for (var j = 1; j < char_scenes.length; j++) {
	    links[links.length] = new Link(char_scenes[j-1], char_scenes[j],
					   chars[i].group, chars[i].id);
	    links[links.length-1].char_ptr = chars[i];
	    //console.log("char name = " + chars[i].name + ", group = " + chars[i].group);
	    char_scenes[j-1].out_links[char_scenes[j-1].out_links.length] = links[links.length-1];
	    char_scenes[j].in_links[char_scenes[j].in_links.length] = links[links.length-1];
	    //console.log(char_scenes[j].in_links[char_scenes[j].in_links.length-1].y0);
	}
    } // for
    return links;
} // generate_links


function Group() {
    this.min = -1;
    this.max = -1;
    this.id = -1;
    this.chars = [];
    this.first_scene_chars = []; // NOT USED?
    this.median_count = 0;
    this.biggest_scene = 0; // largest scene height. NOT USED
    this.all_chars = {};
    this.char_scenes = [];
    this.order = -1;
}


function sort_groups(groups_sorted, groups_desc, top, bottom) {
    if (groups_desc.length == 2) {
	groups_sorted[bottom] = groups_desc[0];
	groups_sorted[top] = groups_desc[1];
	return;
    }
    if (top >= bottom) {
	if (groups_desc.length > 0) {
	    groups_sorted[top] = groups_desc[0];
	}
	return;
    }

    var m = Math.floor((top + bottom)/2);
    groups_sorted[m] = groups_desc[0];
    var t1 = top;
    var b1 = m-1;
    var t2 = m+1;
    var b2 = bottom;
    var g1 = [];
    var g2 = [];
    // TODO: make more efficient
    for (var i = 1; i < groups_desc.length; i++) {
	if (i % 2 == 0) {
	    g1[g1.length] = groups_desc[i];
	} else {
	    g2[g2.length] = groups_desc[i];
	}
    } // for
    sort_groups(groups_sorted, g1, t1, b1);
    sort_groups(groups_sorted, g2, t2, b2);
} // sort_groups


function define_groups(chars) {
    var groups = [];
    chars.forEach(function(c) {
	// Put char in group
	var found_group = false;
	groups.forEach(function(g) {
	    if (g.id == c.group) {
		found_group = true;
		g.chars[g.chars.length] = c;
		c.group_ptr = g;
	    }
	});
	if (!found_group) {
	    g = new Group();
	    g.id = c.group;
	    g.chars[g.chars.length] = c;
	    c.group_ptr = g;
	    groups[groups.length] = g;
	}
    });
    return groups;
}

function find_median_groups(groups, scenes, chars, char_map, tie_breaker) {
    scenes.forEach(function(scene) {
	if (!scene.char_node) {
	    var group_count = [];
	    for (var i = 0; i < groups.length; i++) {
		group_count[i] = 0;
	    }
	    var max_index = 0;

	    scene.chars.forEach(function(c) {
		// TODO: Can just search group.chars
		var group_index = find_group(chars, groups, c);
		group_count[group_index] += 1;
		if ( (!tie_breaker && group_count[group_index] >= group_count[max_index]) ||
		     (group_count[group_index] > group_count[max_index])) {
		    max_index = group_index;
		} else if (group_count[group_index] == group_count[max_index]) {
		    // Tie-breaking
		    var score1 = 0;
		    var score2 = 0;
		    for (var i = 0; i < scene.in_links.length; i++) {
			if (scene.in_links[i].from.median_group != null) {
			    if (scene.in_links[i].from.median_group.id == groups[group_index].id) {
				score1 += 1;
			    } else if (scene.in_links[i].from.median_group.id == groups[max_index].id) {
				score2 += 1;
			    }
			} // if
		    } // for
		    for (var i = 0; i < scene.out_links.length; i++) {
			if (scene.out_links[i].to.median_group != null) {
			    if (scene.out_links[i].to.median_group.id == groups[group_index].id) {
				score1 += 1;
			    } else if (scene.out_links[i].to.median_group.id == groups[max_index].id) {
				score2 += 1;
			    }
			} // if
		    } // for
		    if (score1 > score2) {
			max_index = group_index;
		    }
		} // if
	    }); // for each char in scene
	    scene.median_group = groups[max_index];
	    groups[max_index].median_count += 1;
	    scene.chars.forEach(function(c) {
		// This just puts this character in the set
		// using sets to avoid duplicating characters
		groups[max_index].all_chars[c] = true;
	    });
	}
    });

    // Convert all the group char sets to regular arrays
    groups.forEach(function(g) {
	chars_list = [];
	for (var c in g.all_chars) {
	    chars_list.push(char_map[c]);
	}
	g.all_chars = chars_list;
    });
}


function sort_groups_main(groups, center_sort) {
    groups.sort(function(a, b) {
	return b.median_count - a.median_count;
    });

    var groups_cpy = [];
    for (var i = 0; i < groups.length; i++) {
	groups_cpy[i] = groups[i];
    }

    if (!center_sort) {
	if (groups.length > 0) groups_cpy[0] = groups[0];
	if (groups.length > 1) groups_cpy[groups.length-1] = groups[1];
	if (groups.length > 2) {
	    var groups_desc = [];
	    for (var i = 0; i < groups.length - 2; i++) {
		groups_desc[i] = groups[i+2];
	    }
	    // groups_cpy is the one that gets sorted
	    sort_groups(groups_cpy, groups_desc, 1, groups.length-2);
	}
    } else {
	var center = Math.floor(groups.length/2.0);
	groups_cpy[center] = groups[0];
	var groups_desc1 = [];
	for (var i = 0; i < center; i++) {
	    groups_desc1[i] = groups[i];
	}
	var groups_desc2 = [];
	for (var i = center + 1; i < groups.length; i++) {
	    groups_desc2[i-center-1] = groups[i];
	}
	sort_groups(groups_cpy, groups_desc1, 0, center);
	sort_groups(groups_cpy, groups_desc2, center+1, groups.length);
    }

    for (var i = 0 ; i < groups_cpy.length; i++) {
	groups_cpy[i].order = i;
    }
    return groups_cpy;
} // sort_groups_main


// Called before link positions are determined
function add_char_scenes(chars, scenes, links, groups, panel_shift, comic_name) {
    // Shit starting times for the rest of the scenes panel_shift panels to the left
    var char_scenes = [];
    scenes.forEach(function(scene) {
	if (!equal_scenes) {
	    scene.start += panel_shift;
        }
    });

    // Set y values
    var cury = 0;
    groups.forEach(function(g) {
        var height = g.all_chars.length*text_height;
	g.min = cury;
	g.max = g.min + height;
	cury += height + group_gap;
    });

    for (var i = 0; i < chars.length; i++) {
	var s = new SceneNode([chars[i].id], [0], [1]);
	s.char_node = true;
	s.y = i*text_height;
	s.x = 0;
	s.width = 5;
	s.height = link_width;
	s.name = chars[i].name;
	s.chars[s.chars.length] = chars[i].id;
	s.id = scenes.length;
	s.comic_name = comic_name;
	if (chars[i].first_scene != null) {
	    var l = new Link(s, chars[i].first_scene, chars[i].group, chars[i].id);
	    l.char_ptr = chars[i];

	    s.out_links[s.out_links.length] = l;
	    chars[i].first_scene.in_links[chars[i].first_scene.in_links.length] = l;
	    links[links.length] = l;
	    s.first_scene = chars[i].first_scene;

	    scenes[scenes.length] = s;
	    char_scenes[char_scenes.length] = s;
	    s.median_group = chars[i].first_scene.median_group;
	} // if
    } // for
    return char_scenes;
} // add_char_scenes


// TODO: Use the char_map to eliminate this
function find_group(chars, groups, char_id) {
    // Find the char's group id
    var i;
    for (i = 0; i < chars.length; i++) {
	if (chars[i].id == char_id) break;

    } // for
    if (i == chars.length) {
	console.log("ERROR: char not found, id = " + char_id);
    }

    // Find the corresponding group
    var j;
    for (j = 0; j < groups.length; j++) {
	if (chars[i].group == groups[j].id) break;
    }
    if (j == groups.length) {
	console.log("ERROR: groups not found.");
    }
    return j;
} // find_group


function calculate_node_positions(chars, scenes, total_panels, chart_width,
				  chart_height, char_scenes, groups, panel_width,
				  panel_shift, char_map) {

    // Set the duration of the very last scene to something small
    // so that there isn't a lot of wasted space at the end
    /*
    scenes.sort(function(a, b) { return a.start - b.start; });
    var last = scenes[scenes.length-1];
    last.start = last.duration - 10;
    last.duration = 5;
    */

    scenes.forEach(function(scene) {
    	if (!scene.char_node) {
    		scene.height = Math.max(0, scene.chars.length*link_width + (scene.chars.length - 1)*link_gap);
    		scene.width = panel_width*3;

		    // Average of chars meeting at the scene _in group_
		    var sum1 = 0;
		    var sum2 = 0;
		    var den1 = 0;
		    var den2 = 0;
		    for (var i = 0; i < scene.chars.length; i++) {
		    	var c = char_map[scene.chars[i]];
		    	var y = c.group_positions[scene.median_group.id];
		    	if (!y) continue;
		    	if (c.group.id == scene.median_group.id) {
		    		sum1 += y;
		    		den1 += 1;
		    	} else {
		    		sum2 += y;
		    		den2 += 1;
		    	}
		    }
		    var avg;
		    // If any non-median-group characters appear in the scene, use
		    // the average of their positions in the median group
		    if (den2 != 0) {
		    	avg = sum2/den2;
			    // Otherwise, use the average of the group characters
			} else if (den1 != 0) {
				avg = sum1/den1;
			} else {
				console.log("ERROR: den1 and den2 are 0. Scene "+scene.id+" doesn't have characters?");
				avg = scene.median_group.min;
			}
			scene.y = avg - scene.height/2.0;

			if (equal_scenes) {
				scene.x = scene.start;
			} else {
				scene.x = scene.start*panel_width;
			}
		}
    });

    char_scenes.forEach(function(scene) {
	if (scene.first_scene != null) { // i.e. if it's a char scene node
	    // Position char node right before the char's first scene
	    if (scene.first_scene.x > per_width*raw_chart_width)
		scene.x = scene.first_scene.x - name_shift;
	    else
		scene.x = panel_shift*panel_width - name_shift;
	}
    });
} // calculate_node_positions


// The positions of the nodes have to be set before this is called
// (The positions of the links are determined according to the positions
// of the nodes they link.)
function calculate_link_positions(scenes, chars, groups, char_map) {
    // Sort by x
    // Because the sorting of the in_links will depend on where the link
    // is coming from, so that needs to be calculated first
    //scenes.sort(function(a, b) { return a.x - b.x; });

    // TODO:
    // Actually, sort the in_links such that the sum of the distances
    // between where a link is on the scene node and where its slot
    // is in the group are minimized

    scenes.forEach(function(scene) {
	// TODO: Sort the in_links here
	// Use sort by group for now
	scene.in_links.sort(function(a, b) { return a.char_ptr.group_ptr.order - b.char_ptr.group_ptr.order; });
	scene.out_links.sort(function(a, b) { return a.char_ptr.group_ptr.order - b.char_ptr.group_ptr.order; });

	// We can't calculate the y positions of the in links in the same
	// way we do the out links, because some links come in but don't go
	// out, and we need every link to go out the same position it came in
	// so we flag the unset positions.
	for (var i = 0; i < scene.out_links.length; i++) {
	    scene.out_links[i].y0 = -1;
	}

	var j = 0;
	for (var i = 0; i < scene.in_links.length; i++) {
	    // These are links incoming to the node, so we're setting the
	    // co-cordinates for the last point on the link path
	    scene.in_links[i].y1 = scene.y + i*(link_width+link_gap) + link_width/2.0;
	    scene.in_links[i].x1 = scene.x + 0.5*scene.width;

	    if (j < scene.out_links.length && scene.out_links[j].char_id == scene.in_links[i].char_id) {
		scene.out_links[j].y0 = scene.in_links[i].y1;
		j++;
	    }
	}

	for (var i = 0; i < scene.out_links.length; i++) {
	    if (scene.out_links[i].y0 == -1) {
		scene.out_links[i].y0 = scene.y + i*(link_width+link_gap) + link_width/2.0;
	    }
	    scene.out_links[i].x0 = scene.x + 0.5*scene.width;
	}
    });

} // calculate_link_positions


function draw_nodes(scenes, svg, chart_width, chart_height, folder, safe_name) {
    var node = svg.append("g").selectAll(".node")
      .data(scenes)
    .enter().append("g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
      .attr("scene_id", function(d) { return d.id; })
      .on("mouseover", mouseover)
      .on("mouseout", mouseout)
    .call(d3.behavior.drag()
      .origin(function(d) { return d; })
      .on("dragstart", function() { this.parentNode.appendChild(this); })
      .on("drag", dragmove));

    node.append("rect")
      .attr("width", function(d) { return d.width; })
      .attr("height", function(d) { return d.height; })
      .attr("class", "scene")
      //.style("fill", function(d) { return "#1f77b4"; })
      //.style("stroke", function(d) { return "#0f3a58"; })
      .attr("rx", 20)
      .attr("ry", 10)
    .append("title")
      .text(function(d) { return d.name; });

    // White background for the names
    if (name_bg) {
      node.append("rect")
	.filter(function(d) {
	    return d.char_node;
	})
	.attr("x", function(d) {
	    return -((d.name.length+2)*5);
	})
	.attr("y", function(d) { return -3; })
        .attr("width", function(d) { return (d.name.length+1)*5; })
        .attr("height", 7.5)
	.attr("transform", null)
        .attr("fill", "#fff")
        .style("opacity", 1);
    }


    node.append("text")
        .filter(function(d) {
	    return d.char_node;
	})
	.attr("x", -6)
	.attr("y", function(d) { return 0; })
	.attr("dy", ".35em")
	.attr("text-anchor", "end")
	.attr("transform", null)
        //.attr("background", "#fff")
	.text(function(d) { return d.name; })
        //.style("fill", "#000")
        //.style("stroke", "#fff")
        //.style("stroke-width", "0.5px")
      .filter(function(d) {
	  return false;
	  //return d.x < chart_width / 2;
      })
	.attr("x", function(d) { return 6 + d.width; })
	.attr("text-anchor", "start");


    function mouseover(d) {
		if (d.char_node == true) return;

		var im = new Image();
		im.name = "Scene panel";

		im.id = "scene" + d.id;
		im.src = folder + "/scene_images/scene" + d.id + ".png";
		im.onload = function(e) {
		    var w = this.width;
		    var h = this.height;
		    var x = d.x + d.width;
		    var y = d.y + d.height;
		    if (h > chart_height-y) {
			var max_h = Math.max(y, chart_height-y);
			if (h > max_h) {
			    var ratio = max_h/h;
			    h *= ratio;
			    w *= ratio;
			}
			if (max_h == y) {
			    y -= h + d.height;
			}
		    }
		    if (w > chart_width-x) {
			var max_w = Math.max(x, chart_width-x);
			if (w > max_w) {
			    var ratio = max_w/w;
			    h *= ratio;
			    w *= ratio;
			}
			if (max_w == x) {
			    x -= w + d.width;
			}
		    }
		    svg.append("image")
		        .data([this])
	                .attr("x", x)
	                .attr("y", y)
			.attr("xlink:href", this.src)
		        .attr("transform", null)
	                .style("position", "relative")
			.attr("id", this.id)
		        .attr("class", "scene-image")
			.attr("width", w)
			.attr("height", h);
		} // im.onload

    } // mouseover

    function mouseout(d) {
	//console.log("mouse out");
	// could use d.id to remove just the one image
	d3.selectAll("[class=\"scene-image\"]").remove();
    }

    function dragmove(d) {
	var newy = Math.max(0, Math.min(chart_height - d.height, d3.event.y));
	var ydisp = d.y - newy;
	d3.select(this).attr("transform", "translate("
			     + (d.x = Math.max(0, Math.min(chart_width - d.width, d3.event.x))) + ","
			     + (d.y = Math.max(0, Math.min(chart_height - d.height, d3.event.y))) + ")");
	reposition_node_links(d.id, d.x, d.y, d.width, d.height, svg, ydisp, d.comic_name);
    }
} // draw_nodes

function find_link(links, char_id) {
    for (var i = 0; i < links.length; i++) {
	if (links[i].char_id == char_id) {
	    return links[i];
	}
    }
    return 0;
}


function draw_links(links, svg, safe_name) {
    var link = svg.append("g").selectAll(".link")
          .data(links)
	.enter().append("path")
	  .attr("class", "link")
	  .attr("d", function(d) { return get_path(d); })
          .attr("from", function(d) { return d.from.comic_name + "_" + d.from.id; })
	  .attr("to", function(d) { return d.to.comic_name + "_" +  d.to.id; })
          .attr("charid", function(d) { return d.from.comic_name + "_" + d.char_id; })
	.style("stroke", function(d) { return d3.rgb(color(d.group)).darker(0.5).toString(); })
	  .style("stroke-width", link_width)
          .style("stroke-opacity", "0.6")
          .style("stroke-linecap", "round")
          .on("mouseover", mouseover_cb)
          .on("mouseout", mouseout_cb)

    function mouseover_cb(d) {
	d3.selectAll("[charid=\"" + d.from.comic_name + "_" + d.char_id + "\"]")
	    .style("stroke-opacity", "1");
    }

    function mouseout_cb(d) {
	d3.selectAll("[charid=\"" + d.from.comic_name + "_" + d.char_id + "\"]")
	    .style("stroke-opacity", "0.6");
    }
} // draw_links



function draw_chart(name, safe_name, folder, tie_breaker, center_sort, collapse) {
	raw_chart_width = $('#xkcdChart').width();

    d3.json(folder + "/narrative.json", function(j) {
	var margin = {top: 20, right: 25, bottom: 20, left: 1};
	var width = raw_chart_width - margin.left - margin.right;

	var jscenes = j['scenes'];
	// This calculation is only relevant for equal_scenes = true
	var scene_width = (width-longest_name)/(jscenes.length+1);

	var total_panels = 0;
	var scenes = []
	for (var i = 0; i < jscenes.length; i++) {
	    var duration = parseInt(jscenes[i]['duration']);
	    var start;
	    if (equal_scenes) {
		start = i*scene_width + longest_name;
	    } else {
		start = parseInt(jscenes[i]['start']);
	    }
	    var chars = jscenes[i]['chars'];
	    //if (chars.length == 0) continue;
	    scenes[scenes.length] = new SceneNode(jscenes[i]['chars'],
						  start, duration,
						  parseInt(jscenes[i]['id']));
	    scenes[scenes.length-1].comic_name = safe_name;
	    total_panels += duration;
	} // for

	scenes.sort(function(a, b) { return a.start - b.start; });
	total_panels -= scenes[scenes.length-1].duration;
	scenes[scenes.length-1].duration = 0;


	// Make space at the leftmost end of the chart for character names
	//var total_panels = parseInt(j['panels']);
	var panel_width = Math.min((width-longest_name)/total_panels, 15);
	var panel_shift = Math.round(longest_name/panel_width);
	total_panels += panel_shift;
	panel_width = Math.min(width/total_panels, 15);

	d3.xml(folder + "/characters.xml", function(x) {
	    var xchars = read_chars(x);

	    // Calculate chart height based on the number of characters
	    // TODO: Redo this calculation
	    //var raw_chart_height = xchars.length*(link_width + link_gap + group_gap);// - (link_gap + group_gap);
	    var raw_chart_height = 360;
	    var height = raw_chart_height - margin.top - margin.bottom;

	    // Insert the collapsable title
	    /*
	    var sign, disp;
	    if (collapse) {
		sign = "+";
		disp = "none";
	    } else {
		sign = "-";
		disp = "inherit";
	    }
	    */
	    var svg = d3.select("#xkcdChart").append("text")
	        .attr("x", 0)
		.attr("y", 0)
		.attr("dy", ".35em")
		.attr("text-anchor", "end")
	        .attr("class", "comic-title")
		.attr("transform", null)
	        .attr("id", safe_name)
		.text(" - " + name)
	        .data([{name: " - " + name, folder: folder, safe_name: safe_name}])
	        .style("display", "block")
	        .on("click", function(d) {
		    var nodes = d3.selectAll(".chart").selectAll("[id=\"" + d.safe_name + "\"]");
		    var node;
		    for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].parentNode.id == d.safe_name) {
			    node = nodes[i].parentNode;
			    break;
			}
		    }
		    if (d.name[1] == '-') {
			node.style.display = "none";
			d.name = d.name.replace("-", "+");
		    } else {
			node.style.display = "inherit";
			d.name = d.name.replace("+", "-");
		    }
		    d3.select(this).text(d.name);
	        });

	    var svg = d3.select("#xkcdChart").append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
                .attr("class", "chart")
                .attr("id", safe_name)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");


	    var chars = [];
	    var char_map = []; // maps id to pointer
	    for (var i = 0; i < xchars.length; i++) {
			chars[chars.length] = new Character_(xchars[i].name, xchars[i].id, xchars[i].group);
			char_map[xchars[i].id] = chars[chars.length-1];
	    }

	    var groups = define_groups(chars);
	    find_median_groups(groups, scenes, chars, char_map, tie_breaker);
	    groups = sort_groups_main(groups, center_sort);

	    var links = generate_links(chars, scenes);
	    var char_scenes = add_char_scenes(chars, scenes, links, groups, panel_shift, safe_name);


	    // Determine the position of each character in each group
	    // (if it ever appears in the scenes that appear in that group)
	    groups.forEach(function(g) {
		g.all_chars.sort(function(a, b) {
			return a.group_ptr.order - b.group_ptr.order;
		});
		var y = g.min;
		for (var i = 0; i < g.all_chars.length; i++) {
		    g.all_chars[i].group_positions[g.id] = y + i*(text_height);
		}
	    });


	    calculate_node_positions(chars, scenes, total_panels,
				     width, height, char_scenes, groups, panel_width,
				     panel_shift, char_map);


	    scenes.forEach(function(s) {
		    if (!s.char_node) {
			first_scenes = [];
			//ys = [];
			s.in_links.forEach(function(l) {
				if (l.from.char_node) {
				    first_scenes[first_scenes.length] = l.from;
				    //ys[ys.length] = l.y1;
				    //console.log(l.y1);
				}
			});
			/*
			if (first_scenes.length == 1) {
			    first_scenes[0].y = s.y + s.height/2.0;
			    console.log(first_scenes[0].y);
			} else {
			*/
			for (var i = 0; i < first_scenes.length; i++) {
			    first_scenes[i].y = s.y + s.height/2.0 + i*text_height;
			}
			    //}
		    }
	    });

	    // Determining the y-positions of the names (i.e. the char scenes)
	    // if the appear at the beginning of the chart
	    char_scenes.forEach(function(cs) {

		    var character = char_map[cs.chars[0]];
		    if (character.first_scene.x < per_width*width) {
			    // The median group of the first scene in which the character appears
			    // We want the character's name to appear in that group
				var first_group = character.first_scene.median_group;
				cs.y = character.group_positions[first_group.id];
		    }
	    });

	    calculate_link_positions(scenes, chars, groups, char_map);

	    height = groups[groups.length-1].max + group_gap*5;
	    raw_chart_height = height + margin.top + margin.bottom;
	    d3.select('svg#' + safe_name).style("height", raw_chart_height);

	    /*
	    groups.forEach(function(g) {
		    d3.select("svg#" + folder).append("rect")
			.attr("width", 10)
			.attr("height", g.max-g.min)
			.attr("x", 0)
			.attr("y", g.min+margin.top)
			.style("color", color(g.id));
		});
	    */
	    draw_links(links, svg);
	    draw_nodes(scenes, svg, width, height, folder, raw_chart_height, safe_name);
	}); // d3.xml (read chars)
    }); // d3.json (read scenes)
}




/*
 * catxml.js *****************************************
 *
 */

function get_xml(path) {
    var request = new XMLHttpRequest();
    request.open('GET', path, false);
    request.send(); // because of "false" above, will block until the request is done
    // and status is available. Not recommended, however it works for simple cases.
    if (request.status === 200) {
		return request.responseXML;
    } else {
		alert("ERROR: Couldn't retrieve xml at " + path + "; error status: " + request.status);
    }

}

function get_attribute(element, attr_name, def_value) {
    var value = element.getAttribute(attr_name);
    if (value == null) return def_value
    return value
}

// Returns the name of a comic page whose number is page_number,
// taking into account the padding of the page number and if the basename
// of the comic is prepended or appended to the number to create the name.
function number_to_name(page_number, comic_settings) {
    var num_str = page_number.toString();
    var num_length = num_str.length;
    while (num_length < comic_settings.pad_to) {
	num_str = "0" + num_str;
	num_length += 1;
    }
    var page_name = num_str;
    if (comic_settings.path_creation == "prepend") {
	page_name = comic_settings.basename + page_name;
    } else if (comic_settings.path_creation == "append") {
	page_name = page_name + comic_settings.basename;
    }
    return page_name
}

// class ComicOptions
function ComicOptions(folder_path) {
    var options_xml = get_xml(folder_path + "/options.xml");
    var el = options_xml.getElementsByTagName("folder-options")[0];
    this.basename = el.attributes.getNamedItem("basename").nodeValue;
    this.pad_to = el.attributes.getNamedItem("number-padding").nodeValue;
    this.path_creation = el.attributes.getNamedItem("path-creation").nodeValue;
    this.first_page = el.attributes.getNamedItem("first-page").nodeValue;
    this.ext = el.attributes.getNamedItem("ext").nodeValue;
}

// class Transition
function Transition(trans_xml) {
    //console.log(trans_xml);
    this.from = parseInt(trans_xml.attributes.getNamedItem("from-id").nodeValue);
    this.to = parseInt(trans_xml.attributes.getNamedItem("to-id").nodeValue);
    this.type = parseInt(trans_xml.attributes.getNamedItem("type").nodeValue);
}

// class Vertex
function Vertex(x, y) {
    this.x = x;
    this.y = y;
}

// class Character
function Character(char_xml) {
    //console.log(char_xml);
    this.name = char_xml.getAttribute("name");
    this.id = parseInt(char_xml.getAttribute("id"));
    this.group = parseInt(char_xml.getAttribute("group"));
}

// class TagDef
function TagDef(tag_xml) {
    this.name = tag_xml.getAttribute("name");
    this.id = parseInt(tag_xml.getAttribute("id"));
    this.description = get_attribute(tag_xml, "description", "");
}

// class Tag
function Tag(tag_xml) {
    this.id = parseInt(tag_xml.getAttribute("id"));
    this.confidence = parseInt(get_attribute(tag_xml, "confidence", 10));
    this.intensity = parseInt(get_attribute(tag_xml, "intensity", 10));
}

function read_chars(xml) {
    //var chars_xml = get_xml(folder_path + "/characters.xml");
    var char_elms = xml.getElementsByTagName("character");
    characters = [];
    for (var i = 0; i < char_elms.length; i++) {
	characters[i] = new Character(char_elms[i]);
    }
    return characters
}
