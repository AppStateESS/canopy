<?php

    if(!is_numeric($_GET['r']) || !is_numeric($_GET['b'])) {
        echo "Don't give me bad data.";
        exit(1);
    }
    $p = new piechart();
    $p->init(125,125,array(
        array($_GET['r'], null, 183, 44, 44),
        array($_GET['b'], null, 88, 88, 160)),
        array(0, 0, 0));
    $p->display();

  /* -*- C -*- */
  /*
   ** $Id: piechart.phl,v 1.2 1998/02/03 16:55:06 borud Exp $
   **
   ** PHP Class for creating pie charts using the GD library functions.
   **
   ** There is a bug in the GD library somewhere that seems to kick in
   ** when you try to return images that are larger than 4K. We probably
   ** need a workaround for this...
   **
   ** Pie charts look a bit shabby. There seems to be one or more
   ** roundoff errors lurking about making life hard for us. To fix this
   ** we should perhaps investigate how the Arc-drawing thingey works and
   ** try to find out how it gets the endpoints. Also the flood-filler
   ** doesn't quite cope with filling the pieces very well.
   **
   ** Authors: Bjørn Borud, Guardian Networks AS, <borud@guardian.no>
   */
  /* {{{ piechart */
  /*
   ** This is a class for creating pie charts. Generally you just have
   ** to instantiate it, and then make a call to the "init" method to
   ** set the size and transfer the data.
   **
   ** The data is an array of arrays that consist the following data:
   **o numeric value
   **o value legend
   **o red \
   **o green > the RGB values for the color of the slice/legend
   **o blue /
   **
   */
  class piechart
  {
      /* {{{ attributes */
      var $im;
      var $width, $height;
      var $data;
      var $colors;
      var $linecolor;
      var $angles;
      var $left = 0;
      var $right = 0;
      var $top = 0;
      var $bottom = 0;
      var $head_top = 10;
      var $head_space = 5;
      var $legend_left = 20;
      var $center_x;
      var $center_y;
      var $diameter;
      /* sum of values */
      var $sum;
      /* font sizes */
      var $fx, $fy;
      var $legend_num = "";
      /* }}} */
      /* {{{ constants */
      var $PI = 3.1415926535897931;
      /* }}} */
      /* {{{ roundoff */
      /*
       ** PHP has no function for rounding off doubles to the nearest
       ** integer so we have to roll our own.
       */
      function roundoff($v)
      {
          if ($v - floor($v) >= 0.5) {
              return(ceil($v));
          } else {
              return(floor($v));
          }
      }
      /* }}} */
      /* {{{ deg2rad */
      /*
       ** The built-in trig functions use radians and there's no
       ** function in PHP to convert between degrees and radians
       */
      function deg2rad($degrees)
      {
          return(($this->PI * $degrees) / doubleval(180));
      }
      /* }}} */
      /* {{{ get_xy_factors */
      /*
       ** Calculate the directional vector for the sides of the
       ** piece of pie.
       */
      function get_xy_factors($degrees)
      {
          $x = cos($this->deg2rad($degrees));
          $y = sin($this->deg2rad($degrees));
          return(array($x, $y));
      }
      /* }}} */
      /* {{{ init */
      /*
       ** Initialize the object and draw the pie. This would be the
       ** constructor in an ordinary OO scenario -- just that we haven't
       ** got constructors in PHP, now do we? ;-)
       */
      function init($w, $h, $d, $l)
      {
          $this->im = imagecreate($w, $h);
          $this->width = $w;
          $this->height = $h;
          $this->data = $d;
          $this->da_width = ($this->width - $this->left - $this->right);
          $this->da_height = ($this->height - $this->top - $this->bottom);
          $this->center_x = intval($this->left + ($this->da_width / 2));
          $this->center_y = intval($this->top + ($this->da_height / 2));
          /* font sizes */
          $this->fx = array(0, 5, 6, 7, 8, 9);
          $this->fy = array(0, 7, 8, 10, 14, 11);
          /* decide the diameter of the pie */
          if ($this->da_height > $this->da_width) {
              $this->diameter = $this->da_width;
          } else {
              $this->diameter = $this->da_height;
          }
          $this->white = imagecolorallocate($this->im, 255, 255, 255);
          $this->black = imagecolorallocate($this->im, 0, 0, 0);
          $this->linecolor = imagecolorallocate($this->im, $l[0], $l[1], $l[2]);
          $n = count($this->data);
          for ($i = 0; $i < $n; $i++) {
              $this->colors[$i] = imagecolorallocate($this->im, $this->data[$i][2], $this->data[$i][3], $this->data[$i][4]);
              $this->sum += $this->data[$i][0];
          }
          $from = 0;
          $to = 0;
          // Draw Circle
          imagearc($this->im, $this->center_x, $this->center_y, $this->diameter, $this->diameter, 0, 360, $this->linecolor);
          for ($i = 0; $i < $n; $i++) {
              $this->angles[$i] = $this->roundoff(($this->data[$i][0] * 360) / doubleval($this->sum));
              $to = $from + $this->angles[$i];
              $col = $this->colors[$i];
              
              $foo = $this->angles[$i];
              $this->draw_slice($this->center_x, $this->center_y, $from, $to, $this->colors[$i]);
              $from += $this->angles[$i];
          }
      }
      /* }}} */
      /* {{{ set_legend_percent */
      /* utility function to set an attribute so we display percentages */
      function set_legend_percent()
      {
          $this->legend_num = "p";
      }
      /* }}} */
      /* {{{ set_legend_value */
      /* utility function to set an attribute so we display values */
      function set_legend_value()
      {
          $this->legend_num = "v";
      }
      /* }}} */
      /* {{{ draw_point */
      /*
       ** This function is just here for debugging purposes. It is
       ** sometimes very useful to be able to draw an X to check
       ** coordinates.
       */
      function draw_point($x, $y)
      {
          imageline($this->im, $x - 4, $y - 4, $x + 4, $y + 4, $this->black);
          imageline($this->im, $x - 4, $y + 4, $x + 4, $y - 4, $this->black);
      }
      /* }}} */
      /* {{{ draw_margins */
      /*
       ** Also a debugging function to show where the margins are at
       */
      function draw_margins()
      {
          imageline($this->im, 0, $this->top, $this->width, $this->top, $this->black);
          imageline($this->im, 0, $this->height - $this->bottom, $this->width, $this->height - $this->bottom, $this->black);
          imageline($this->im, $this->left, 0, $this->left, $this->height, $this->black);
          imageline($this->im, $this->width - $this->right, 0, $this->width - $this->right, $this->height, $this->black);
      }
      /* }}} */
      /* {{{ draw_legends */
      /*
       ** Draw legends at the right side of the pie chart. This function
       ** accepts a fontsize and gathers all the other information from
       ** the multilevel data array
       */
      function draw_legends($fontsize)
      {
          $n = count($this->data);
          $x1 = $this->width - $this->right + $this->legend_left;
          $x2 = $x1 + $this->fy[$fontsize];
          for ($i = 0; $i < $n; $i++) {
              /* determine Y coordinates */
              $y1 = ($i * $this->fy[$fontsize] * 1.5) + $this->top;
              $y2 = $y1 + $this->fy[$fontsize];
              /* draw the legend color rectangle */
              imagefilledrectangle($this->im, $x1, $y1, $x2, $y2, $this->colors[$i]);
              imagerectangle($this->im, $x1, $y1, $x2, $y2, $this->black);
              $legend = $this->data[$i][1];
              /* decide what to show after legend */
              switch ($this->legend_num) {
                  case "v":
                      $legend .= sprintf(" (%.2f)", $this->data[$i][0]);
                      break;
                  case "p":
                      $legend .= sprintf(" (%.2f%%)", ($this->data[$i][0] * 100 / doubleval($this->sum)));
                      break;
              }
              imagestring($this->im, $fontsize, $x2 + 5, $y1, $legend, $this->black);
          }
      }
      /* }}} */
      /* {{{ draw_heading */
      /*
       ** This function accepts an array of arrays containing (in order):
       ** o The text of the heading as a string
       ** o The fontsize as an integer
       ** o The justification ("c"=center)
       **
       */
      function draw_heading($head_data)
      {
          $n = count($head_data);
          $y = $this->head_top;
          for ($i = 0; $i < $n; $i++) {
              switch ($head_data[$i][2]) {
                  case "c":
                      $x = ($this->width - $this->fx[$head_data[$i][1]] * strlen($head_data[$i][0])) / 2;
                      break;
                  case "r":
                      /* uses left margin here... */
                      $x = $this->width - $this->left - ($this->fx[$head_data[$i][1]] * strlen($head_data[$i][0]));
                      break;
                  default:
                      $x = $this->left;
                      break;
              }
              imagestring($this->im, $head_data[$i][1], $x, $y, $head_data[$i][0], $this->black);
              $y += ($this->fy[$head_data[$i][1]] + $this->head_space);
          }
      }
      /* }}} */
      /* {{{ draw_slice */
      /*
       ** This function draws a piece of pie centered at x,y starting at
       ** "from" degrees and ending at "to" degrees using the specified color.
       */
      function draw_slice($x, $y, $from, $to, $color)
      {
          // Awful Kludge!!!
          if ($to > 360) {
              $to = 360;
          }
          /* First line */
          $axy2 = $this->get_xy_factors($from);
          $ax2 = floor($this->center_x + ($axy2[0] * ($this->diameter / 2)));
          $ay2 = floor($this->center_y + ($axy2[1] * ($this->diameter / 2)));
          imageline($this->im, $this->center_x, $this->center_y, $ax2, $ay2, $this->linecolor);
          /* Second line */
          $bxy2 = $this->get_xy_factors($to);
          $bx2 = ceil($this->center_x + ($bxy2[0] * ($this->diameter / 2)));
          $by2 = ceil($this->center_y + ($bxy2[1] * ($this->diameter / 2)));
          //imageline($this->im, $this->center_x, $this->center_y, $bx2, $by2, $this->linecolor);
          /* decide where to start filling, then fill */
          $xy2 = $this->get_xy_factors((($to - $from) / 2) + $from);
          $x2 = floor($this->center_x + ($xy2[0] * ($this->diameter / 3)));
          $y2 = floor($this->center_y + ($xy2[1] * ($this->diameter / 3)));
          imagefilltoborder($this->im, $x2, $y2, $this->linecolor, $color);
      }
      /* }}} */
      /* {{{ display */
      /*
       ** Make sure the legends are drawn, then output the image to the
       ** client
       */
      function display()
      {
          //$this->draw_legends(2);
          //$this->draw_margins();
          header("Content-type: image/png");
          imagepng($this->im);
      }
      /* }}} */
      };
      /* }}} */
      /* {{{ Test code
      $vals = array(array(100, "First value", 190, 0, 0), array(100, "Second value", 0, 190, 0), array(100, "Third value", 0, 0, 190), array(100, "Fourth value", 0, 190, 190), array(301.2437, "Fifth value", 204, 0, 204), array(308, "Sixth value", 204, 204, 0));
      $heads = array(array("First line (centered)", 4, "c"), array("Second line (left justified)", 4, "l"), array("Third line (right justified)", 4, "r"));
      $pie = new piechart;
      $pie->init(400, 300, $vals);
      $pie->draw_heading($heads);
      $pie->set_legend_percent();
      $pie->display();
      /* }}} */
      /*
       * Local Variables:
       * tab-width: 3
       * End: */
?>

