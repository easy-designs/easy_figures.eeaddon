<?php
/*
=====================================================
 ItFigures - by Easy! Designs, LLC
-----------------------------------------------------
 http://www.easy-designs.net/
=====================================================
 This extension was created by Aaron Gustafson
 - aaron@easy-designs.net
 This work is licensed under the MIT License.
=====================================================
 File: pi.itfigures.php
-----------------------------------------------------
 Purpose: Translates single images in paragraphs
 into figures
=====================================================
*/

$plugin_info = array(
  'pi_name'        => 'ItFigures',
  'pi_version'     => '1.0',
  'pi_author'      => 'Aaron Gustafson',
  'pi_author_url'	 => 'http://easy-designs.net/',
  'pi_description' => 'Builds microformat-ed figures from basic markup',
  'pi_usage'       => ItFigures::usage()
);

class ItFigures {

  var $return_data;
  var $left_class  = 'align-left';
  var $right_class = 'align-right';
  # these are the default settings for figure lookup
  var $figure_wrap = 'p';
  var $credit_wrap = 'em';
  var $legend_wrap = 'strong';
  # these are the default settings for figure output
  var $figure_el    = 'div';
  var $content_el   = '';
  var $credit_el    = 'p';
  var $legend_el    = 'p';
  var $output_order = 'img|credit|legend';
  
  # the HTML skeleton for output
  var $figure  = '<{element} class="figure{classes}">{content}</{element}>';
  var $img     = '<img class="image" src="{src}" alt="{alt}" {optional}/>';
  var $content = '<{element}>{content}</{element}>';
  var $credit  = '<{element} class="credit">{content}</{element}>';
  var $legend  = '<{element} class="legend">{content}</{element}>';
  
  /**
   * ItFigures constructor
   * sets any overrides and triggers the processing
   * 
   * @param str $str - the content to be parsed
   */
  function ItFigures ( $str = '' )
  {
    
    # get any tag overrides
    global $TMPL;
    if ( $temp = $TMPL->fetch_param('left_class') ) $this->left_class = $temp;
    if ( $temp = $TMPL->fetch_param('right_class') ) $this->right_class = $temp;
    if ( $temp = $TMPL->fetch_param('figure_wrap') ) $this->figure_wrap = $temp;
    if ( $temp = $TMPL->fetch_param('credit_wrap') ) $this->credit_wrap = $temp;
    if ( $temp = $TMPL->fetch_param('legend_wrap') ) $this->legend_wrap = $temp;
    if ( $temp = $TMPL->fetch_param('figure_el') ) $this->figure_el = $temp;
    if ( $temp = $TMPL->fetch_param('content_el') ) $this->content_el = $temp;
    if ( $temp = $TMPL->fetch_param('credit_el') ) $this->credit_el = $temp;
    if ( $temp = $TMPL->fetch_param('legend_el') ) $this->legend_el = $temp;
    if ( $temp = $TMPL->fetch_param('output_order') ) $this->output_order = $temp;
    
    # Fetch string
    if ( empty( $str ) ) $str = $TMPL->tagdata;
    
    # return the processed string
    $this->return_data = ( ! empty( $str ) ? $this->process( $str ) : $str );
  
  } # end ItFigures constructor
  
  /**
   * ItFigures::process()
   * processes the supplied content based on the configuration
   * 
   * @param str $str - the content to be parsed
   */
  function process( $str )
  {
    global $FNS;
    
    # trim
    $str = trim( $str );
    
    $lookup = '/(<' . $this->figure_wrap . '.*>.*<img.*\/>.*<\/' . $this->figure_wrap . '>)/';
    if ( preg_match_all( $lookup, $str, $found, PREG_SET_ORDER ) )
    {
      # loop the matches
      foreach ( $found as $instance )
      {
        # extract the image
        $img = preg_replace( '/.*(\<img.*\/\>).*/', '$1', $instance[1] );
        # get the source
        $src   = preg_replace( '/.*src=["\'](.+?)[\'"].*/', '$1', $img );
        # get title, alt, etc.
        preg_match( '/.*title=["\'](.+?)[\'"].*/', $img, $matches );
        $title = count( $matches ) ?  $matches[1] : '';
        preg_match( '/.*alt=["\'](.+?)[\'"].*/', $img, $matches );
        $alt   = count( $matches ) ?  $matches[1] : '';
        # set the alignment (if any)
        $align = FALSE;
        if ( preg_match( '/\<img.*?style=["\'].*?float\s*?:\s*?(right|left)\s*?\;[\'"].*?\/\>/', $img, $matches ) )
        {
          $align = $matches[1];
        }
        elseif ( preg_match( '/\<img.*align=["\'](right|left)[\'"].*?\/\>/', $img, $matches ) )
        {
          $align = $matches[1];
        }
        # build the new image
        $swap = array(
          'src'      => $src,
          'optional' => ( ! empty( $title ) ? ' title="' . $title . '"' : '' ),
          'alt'      => $alt
        );
        $img = $FNS->var_swap( $this->img, $swap );
        
        # get the credit line (if it exists)
        $credit = '';
        $regex = '/.*\<'. $this->credit_wrap . '\>(.*?)\<\/' . $this->credit_wrap . '\>.*/';
        if ( preg_match( $regex, $instance[1], $matches ) )
        {
          $swap = array(
            'element' => $this->credit_el,
            'content' => $matches[1]
          );
          $credit = $FNS->var_swap( $this->credit, $swap );
        }
    
        # get the legend (if it exists)
        $legend = '';
        $regex = '/.*\<'. $this->legend_wrap . '\>(.*?)\<\/' . $this->legend_wrap . '\>.*/';
        if ( preg_match( $regex, $instance[1], $matches ) )
        {
          $swap = array(
            'element' => $this->legend_el,
            'content' => $matches[1]
          );
          $legend = $FNS->var_swap( $this->legend, $swap );
        }
        
        # prepare the content bits
        $content = array();
        $temp = explode( '|', $this->output_order );
        $img_position = 'first';
        foreach ( $temp as $chunk )
        {
          if ( $chunk == 'img' )
          {
            if ( $chunk == $temp[2] )
            {
              $img_position = 'last';
            }
          }
          else
          {
            if ( ! empty( $$chunk ) ) $content[] = $$chunk;
          }
        }
        if ( ! empty( $this->content_el ) )
        {
          $swap = array(
            'element' => $this->content_el,
            'content' => implode( ' ', $content )
          );
          $content = array( $FNS->var_swap( $this->content, $swap ) );
        }
        if ( $img_position == 'first' )
        {
          array_unshift( $content, $img );
        }
        else
        {
          $content[] = $img;
        }
        $content = implode( ' ', $content );
    
        # inject into the figure
        $class = '';
        if ( $align && $align == 'right' )
        {
          $class = ' ' . $this->right_class;
        }
        elseif ( $align && $align == 'left' )
        {
          $class = ' ' . $this->left_class;
        }
        $swap = array(
          'element' => $this->figure_el,
          'classes' => $class,
          'content' => $content
        );
    
        # replace
        $str = str_replace( $instance[1], $FNS->var_swap( $this->figure, $swap ), $str );
      } # end foreach instance
    } # end if match
    
    return $str;
  } # end ItFigures::process()
    
  /**
   * ItFigures::usage()
   * Describes how the plugin is used
   */
  function usage()
  {
    ob_start(); ?>
Want to add some microformats support to your site _and_ gain additional CSS hooks around your images? If so, you've come to the right place. ItFigures comes with a set of sensible default that will allow you to use it right away. By default, it looks for images that sit inside a paragraph by themselves (or, optionally, with additional text).

By implementing it with no options:

{exp:itfigures}{body}{/exp:itfigures}

when the plugin encounters

<p><img src="foo.png" alt=""/></p>

it will remake that as

<div class="figure"><img class="image" src="foo.png" alt="" /></div>

Providing it with additional element hooks in the content allows it to build a more robust figure:

<p><img src="foo.png" alt="" style="float: right;"/> <em>Photo by Aaron</em> <strong>This is a sample image</strong></p>

will become

<div class="figure align-right">
  <img class="image" src="foo.png" alt="" />
  <p class="credit">Photo by Aaron</p>
  <p class="legend">This is a sample image</p>
</div>

making it quite simple to quickly generate figures without having to remember the markup.

If you want to customize things further, you can use any or all of the optional properties:

* left_class: the class for left-alignment ("align-left" by default)
* right_class: the class for right-alignment ("align-right" by default)
* figure_wrap: the wrapper element for the whole figure that we should look for ("p" by default)
* credit_wrap: the wrapper element for the credit ("em" by default)
* legend_wrap: the wrapper element for the legend ("strong" by default)
* figure_el: the element you wish to use as the figure container ("div" by default)
* content_el: the element you wish to use as the text content container (empty by default)
* credit_el: the element you wish to use as the credit container ("p" by default)
* legend_el: the element you wish to use as the legend container ("p" by default)
* output_order: the order in which you want to output the contents into the figure ("img|credit|legend" by default, "img" must come first or last, but cannot come in the middle)

<?php
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
  } # end ItFigures::usage()

} # end ItFigures

?>