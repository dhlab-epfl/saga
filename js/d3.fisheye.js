/*
 * Fisheye Distortion
 *
 * Demo: http://bost.ocks.org/mike/fisheye/
 *
 * Implements a fisheye distortion for two-dimensional layouts. Based on Sarkar and Brown’s Graphical Fisheye Views of Graphs (CHI '92), as well as Flare's FisheyeDistortion and Sigma.js's fisheye example.
 *
 * When constructing a fisheye distortion, you can specify the radius and distortion factor:
 *
 * var fisheye = d3.fisheye.circular()
 *     .radius(200)
 *     .distortion(2);
 * Typically, you then update the focal point of the distortion on mousemove:
 *
 * svg.on("mousemove", function() {
 *    fisheye.focus(d3.mouse(this));
 * });
 * The distortion operator takes as input an object with x and y attributes, and returns a new object with x, y and z attributes. The returned object represents the distorted position of the input object; the z property is a scaling factor so that you can optionally distort the size of elements as well.
 *
 * For example, to apply fisheye distortion to a force layout, stash the distorted positions in a display property on each node, and then use the distorted positions to update the nodes and links:
 *
 * svg.on("mousemove", function() {
 *   fisheye.focus(d3.mouse(this));
 *
 *   node.each(function(d) { d.fisheye = fisheye(d); })
 *       .attr("cx", function(d) { return d.fisheye.x; })
 *       .attr("cy", function(d) { return d.fisheye.y; })
 *       .attr("r", function(d) { return d.fisheye.z * 4.5; });
 *
 *   link.attr("x1", function(d) { return d.source.fisheye.x; })
 *       .attr("y1", function(d) { return d.source.fisheye.y; })
 *       .attr("x2", function(d) { return d.target.fisheye.x; })
 *       .attr("y2", function(d) { return d.target.fisheye.y; });
 * });
 * There's also a d3.fisheye.scale for Cartesian distortion; see the above demo for an example.
 *
 */

(function() {
  d3.fisheye = {
    scale: function(scaleType) {
      return d3_fisheye_scale(scaleType(), 3, 0);
    },
    circular: function() {
      var radius = 200,
          distortion = 2,
          k0,
          k1,
          focus = [0, 0];

      function fisheye(d) {
        var dx = d.x - focus[0],
            dy = d.y - focus[1],
            dd = Math.sqrt(dx * dx + dy * dy);
        if (!dd || dd >= radius) return {x: d.x, y: d.y, z: dd >= radius ? 1 : 10};
        var k = k0 * (1 - Math.exp(-dd * k1)) / dd * .75 + .25;
        return {x: focus[0] + dx * k, y: focus[1] + dy * k, z: Math.min(k, 10)};
      }

      function rescale() {
        k0 = Math.exp(distortion);
        k0 = k0 / (k0 - 1) * radius;
        k1 = distortion / radius;
        return fisheye;
      }

      fisheye.radius = function(_) {
        if (!arguments.length) return radius;
        radius = +_;
        return rescale();
      };

      fisheye.distortion = function(_) {
        if (!arguments.length) return distortion;
        distortion = +_;
        return rescale();
      };

      fisheye.focus = function(_) {
        if (!arguments.length) return focus;
        focus = _;
        return fisheye;
      };

      return rescale();
    }
  };

  function d3_fisheye_scale(scale, d, a) {

    function fisheye(_) {
      var x = scale(_),
          left = x < a,
          range = d3.extent(scale.range()),
          min = range[0],
          max = range[1],
          m = left ? a - min : max - a;
      if (m == 0) m = max - min;
      return (left ? -1 : 1) * m * (d + 1) / (d + (m / Math.abs(x - a))) + a;
    }

    fisheye.distortion = function(_) {
      if (!arguments.length) return d;
      d = +_;
      return fisheye;
    };

    fisheye.focus = function(_) {
      if (!arguments.length) return a;
      a = +_;
      return fisheye;
    };

    fisheye.copy = function() {
      return d3_fisheye_scale(scale.copy(), d, a);
    };

    fisheye.nice = scale.nice;
    fisheye.ticks = scale.ticks;
    fisheye.tickFormat = scale.tickFormat;
    return d3.rebind(fisheye, scale, "domain", "range");
  }
})();