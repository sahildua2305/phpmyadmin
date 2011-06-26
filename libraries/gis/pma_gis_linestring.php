<?php
/**
 * Handles the visualization of GIS LINESTRING objects.
 *
 * @package phpMyAdmin
 */
class PMA_GIS_Linestring extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public function scaleRow($spatial)
    {
        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));
        return $this->setMinMax($linesrting, array());
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      Label for the GIS LINESTRING object
     * @param string $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));
        $points_arr = $this->extractPoints($linesrting, $scale_data);

        foreach ($points_arr as $point) {
            if (! isset($temp_point)) {
                $temp_point = $point;
            } else {
                // draw line section
                imageline($image, $temp_point[0], $temp_point[1], $point[0], $point[1], $color);
                $temp_point = $point;
            }
        }
        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      Label for the GIS LINESTRING object
     * @param string $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $line = array('width' => 1.5, 'color' => array($r, $g, $b));

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));
        $points_arr = $this->extractPoints($linesrting, $scale_data);

        foreach ($points_arr as $point) {
            if (! isset($temp_point)) {
                $temp_point = $point;
            } else {
                // draw line section
                $pdf->Line($temp_point[0], $temp_point[1], $point[0], $point[1], $line);
                $temp_point = $point;
            }
        }
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      Label for the GIS LINESTRING object
     * @param string $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $line_color, $scale_data)
    {
        $line_options = array(
            'name'        => $label,
            'id'          => $label . rand(),
            'class'       => 'linestring vector',
            'fill'        => 'none',
            'stroke'      => $line_color,
            'stroke-width'=> 2,
        );

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));
        $points_arr = $this->extractPoints($linesrting, $scale_data);

        $row = '<polyline points="';
        foreach ($points_arr as $point) {
            $row .= $point[0] . ',' . $point[1] . ' ';
        }
        $row .= '"';
        foreach ($line_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim($val) . '"';
        }
        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS LINESTRING object
     * @param string $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, $srid, $label, $line_color, $scale_data)
    {
        $style_options = array(
            'strokeColor' => $line_color,
            'strokeWidth' => 2,
            'label'       => $label,
            'fontSize'    => 10,
        );
        if ($srid == 0) {
            $srid = 4326;
        }
        $result = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = substr($spatial, 11, (strlen($spatial) - 12));
        $points_arr = $this->extractPoints($linesrting, null);

        $row = 'new Array(';
        foreach ($points_arr as $point) {
            $row .= '(new OpenLayers.Geometry.Point(' . $point[0] . ', ' . $point[1] . '))'
                . '.transform(new OpenLayers.Projection("EPSG:' . $srid . '"), map.getProjectionObject()), ';
        }
        $row = substr($row, 0, strlen($row) - 2);
        $row .= ')';

        $result .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.LineString(' . $row . '), null, '
            . json_encode($style_options) . '));';
        return $result;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array $gis_data GIS data
     * @param int   $index    Index into the parameter object
     *
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt($gis_data, $index)
    {
        $no_of_points = isset($gis_data[$index]['LINESTRING']['no_of_points'])
            ? $gis_data[$index]['LINESTRING']['no_of_points'] : 2;
        if ($no_of_points < 2) {
            $no_of_points = 2;
        }
        $wkt = 'LINESTRING(';
        for ($i = 0; $i < $no_of_points; $i++) {
            $wkt .= (isset($gis_data[$index]['LINESTRING'][$i]['x'])
                ? $gis_data[$index]['LINESTRING'][$i]['x'] : '')
                . ' ' . (isset($gis_data[$index]['LINESTRING'][$i]['y'])
                ? $gis_data[$index]['LINESTRING'][$i]['y'] : '') .',';
        }
        $wkt = substr($wkt, 0, strlen($wkt) - 1);
        $wkt .= ')';
        return $wkt;
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     * @param index  $index of the geometry
     *
     * @return  parameters for the GIS data editor from the value of the GIS column
     */
    public function generateParams($value, $index = -1)
    {
        if ($index == -1) {
            $index = 0;
            $params = array();
            $last_comma = strripos($value, ",");
            $params['srid'] = trim(substr($value, $last_comma + 1));
            $wkt = trim(substr($value, 1, $last_comma - 2));
        } else {
            $params[$index]['gis_type'] = 'LINESTRING';
            $wkt = $value;
        }

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = substr($wkt, 11, (strlen($wkt) - 12));
        $points_arr = $this->extractPoints($linestring, null);

        $no_of_points = count($points_arr);
        $params[$index]['LINESTRING']['no_of_points'] = $no_of_points;
        for ($i = 0; $i < $no_of_points; $i++) {
            $params[$index]['LINESTRING'][$i]['x'] = $points_arr[$i][0];
            $params[$index]['LINESTRING'][$i]['y'] = $points_arr[$i][1];
        }

        return $params;
    }
}
?>
