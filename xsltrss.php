<?php

/**
 * Plugin Name: XSLT RSS
 * Plugin URI: http://jp.jixor.com/plugins/xslt-rss
 * Description: <strong>This plugin is no longer supported, please upgrade to my much better <a href="http://jp.jixor.com/plugins/advanced-rss/">Advanced RSS Plugin</a>.</strong> More advanced RSS widget that applies xsl translations to your feeds. Once activated, before you create your first widget, please visit the <a href="themes.php?page=xsltrss_options">XSLT RSS Page</a> to ensure your environment is correctly configured and supports the required features.
 * Version: 0.3.3
 * Author: Stephen Ingram
 * Author URI: http://blog.jixor.com
 *
 * RSS feed agregator widget which uses xsl transfomations to format a feed.
 *
 * @author Stephen Ingram <code@jixor.com>
 * @copyright Copyright (c) 2008, Stephen Ingram
 * @package xsltrss
 * @category xsltrss
 * @todo Use socket connect to send proper headers, if-modified-since so that
 *       feed cache is only updated if the feed has changed since it was last
 *       modified. Of course not all servers will send proper headers and respnd
 *       properly, however this is still really a must!
 */

define(
    'JP_WIDGET_XSLTRSS_XSL',
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'xsl'
    );

define(
    'JP_WIDGET_XSLTRSS_CACHE',
    WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache'
    );

function jp_widget_xsltrss($args, $widget_args = 1)
{

    /**
     * Wordpress at some point calls the widget in an improper fashion causing
     * a bad cache, if it appears to be such a situration cache will be
     * dissabled.
     */
    $donotcache = ($args['before_title'] == '%BEG_OF_TITLE%'
        ? true
        : false
        );



    /**
     * name => sidebar name
     * id => sidebar id
     * before_widget
     * after_widget
     * before_title
     * after_title
     * widget_id
     * widget_name
     */
    extract($args);

    /**
     * number => unique widget id, i.e. "225194951"
     */
    extract($widget_args);

    /**
     * url, title, link, items, maxage
     */
    $options = get_option('jp_widget_xsltrss');

    if (!isset($options[$number]))
		return;

	if (isset($options[$number]['error']) && $options[$number]['error'])
		return;

    extract($options[$number]);

    $cache = JP_WIDGET_XSLTRSS_CACHE . DIRECTORY_SEPARATOR . md5($url) . '_xsltrss.xml';

    /**
     * If cache exists and is newer than the maxage display it and return.
     */
    if (file_exists($cache)
        && filemtime($cache) > (time() - $maxage)
        ) {
        echo file_get_contents($cache);
        return;
    }


    /**
     * Spoof user agent, necessary for some feeds, like facebook, for some
     * strange reason only known to that site's developers.
     */
    $old = ini_get('user_agent');

    ini_set(
        'user_agent',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1)'
        );

    /**
     * Get feed using file_get_content - requires fopen wrappers enabled.
     */
    $feed = file_get_contents($url);

    if (!$feed)
        return;

    /**
     * Return the user_agent setting
     */
    ini_set('user_agent', $old);



    /**
     * Initialize and load the rss DOMDocument object.
     */
    $rss = new DOMDocument;
    $rss->loadXML($feed);



    /**
     * If title or link specified modify the feed
     */
    if ($title || $link)
        $xpath = new DOMXPath($rss);

    if ($title) {
        $nodes = $xpath->query('/rss/channel/title');
        $nodes->item(0)->nodeValue = $title;
    }

    if ($link) {
        $nodes = $xpath->query('/rss/channel/link');
        $nodes->item(0)->nodeValue = $link;
    }



    /**
     * Initialize and load the xsl template DOMDocument object.
     */
    $xsl = new DOMDocument;
    $xsl->load(JP_WIDGET_XSLTRSS_XSL . DIRECTORY_SEPARATOR . $template);

    /**
     * Initialize and configure the XSLTProcessor
     */
    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);

    $proc->registerPHPFunctions(array('jp_xslt_isodate'));

    $proc->setParameter('', 'url', $url);
    $proc->setParameter('', 'items', $items);
    $proc->setParameter('', 'rss_icon', includes_url('images/rss.png'));
    $proc->setParameter('', 'before_widget', $before_widget);
    $proc->setParameter('', 'before_title', $before_title);
    $proc->setParameter('', 'after_title', $after_title);
    $proc->setParameter('', 'after_widget', $after_widget);

    /**
     * Apply the transformation
     */
    $out = $proc->transformToXML($rss);

    /**
     * Save the cache, unless do_not_cache is set to true.
     */
    if (!$do_not_cache)
        file_put_contents($cache, $out);

    echo $out;

}

function jp_widget_xsltrss_getfiles()
{

    $templates = array();
    $d = dir(JP_WIDGET_XSLTRSS_XSL);
    while (false !== ($entry = $d->read()))
        if(substr($entry, strrpos($entry,'.') + 1) == 'xsl')
            $templates[] = $entry;
    $d->close();
    return $templates;

}

/**
 * Basically copied from wordpress text widget
 */
function jp_widget_xsltrss_control($widget_args)
{

	global $wp_registered_widgets;
	static $updated = false;

	if (is_numeric($widget_args))
		$widget_args = array('number' => $widget_args);
	$widget_args = wp_parse_args($widget_args, array('number' => -1));
	extract($widget_args, EXTR_SKIP);

    $options = get_option('jp_widget_xsltrss');
	if (!is_array($options))
		$options = array();

	$urls = array();
	foreach ($options as $option)
		if (isset($option['url']))
			$urls[$option['url']] = true;

	if (!$updated
        && 'POST' == $_SERVER['REQUEST_METHOD']
        && !empty($_POST['sidebar'])
        ) {

		$sidebar = (string)$_POST['sidebar'];

		$sidebars_widgets = wp_get_sidebars_widgets();
		if (isset($sidebars_widgets[$sidebar])) {
			$this_sidebar =& $sidebars_widgets[$sidebar];
		} else {
			$this_sidebar = array();
		}

		foreach ($this_sidebar as $_widget_id)
			if ('jp_widget_xsltrss' == $wp_registered_widgets[$_widget_id]['callback']
                && isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
                ) {
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				if ( !in_array( "xsltrss-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
					unset($options[$widget_number]);
			}

		foreach ((array)$_POST['widget-xsltrss'] as $widget_number => $widget_rss) {

			if (!isset($widget_rss['url'])
                && isset($options[$widget_number])
                ) // user clicked cancel
				continue;

			$widget_rss = stripslashes_deep($widget_rss);

			$url = sanitize_url(strip_tags($widget_rss['url']));
			$options[$widget_number] = jp_widget_xsltrss_process($widget_rss, !isset($urls[$url]));

		}

		update_option('jp_widget_xsltrss', $options);

		$updated = true;

	}



    /**
     * Get available XSL template files for <select>
     */
    $templates = jp_widget_xsltrss_getfiles();

    /**
     * Define defaults or get settings if editing.
     */
    if (-1 == $number) {
        $title      = '';
        $url        = '';
        $link       = '';
        $items      = 10;
        $template   = 'default.xsl';
        $number     = '%i%';
        $maxage     = 21600;
    } else {
        $title      = attribute_escape($options[$number]['title']);
        $url        = attribute_escape($options[$number]['url']);
        $link       = attribute_escape($options[$number]['link']);
        $items      = attribute_escape($options[$number]['items']);
        $template   = attribute_escape($options[$number]['template']);     
        $number     = attribute_escape($number);
        $maxage     = attribute_escape($options[$number]['maxage']);
    }

    /**
     * Build the form.
     */
    $out = '<p>
            <label for="xsltrss-url-' . $number . '">'
                . _('Enter the RSS feed URL here:') . '</label>
            <input class="widefat" id="xsltrss-url-' . $number . '"
                name="widget-xsltrss[' . $number . '][url]" type="text"
                value="' . $url . '" />
    	</p>
        <p>
            <label for="xsltrss-title-' . $number . '">'
                . _('Give the feed a title (optional):') . '</label>
            <input class="widefat" id="xsltrss-title-' . $number . '"
                name="widget-xsltrss[' . $number . '][title]" type="text"
                value="' . $title . '" />
        </p>
        <p>
            <label for="xsltrss-link-' . $number . '">'
                . _('Link (optional)') . ':</label>
            <input class="widefat" id="xsltrss-link-' . $number . '"
                name="widget-xsltrss[' . $number . '][link]" type="text"
                value="' . $link . '" />
        </p>
        <p>
            <label for="xsltrss-maxage-' . $number . '">'
                . _('Cache time (seconds)') . ':</label>
            <input id="xsltrss-maxage-' . $number . '"
                name="widget-xsltrss[' . $number . '][maxage]" type="text"
                value="' . $maxage . '" />
        </p>
        <p>
            <label for="xsltrss-items-' . $number . '">'
                . _('How many items would you like to display?') . '</label>
            <select id="xsltrss-items-' . $number . '"
                name="widget-xsltrss[' . $number . '][items]">';

    for ($i = 1; $i <= 20; ++$i)
        $out .= '<option value="' . $i . '"' . ($items == $i
            ? ' selected="selected"'
            : ''
            )
            . ">$i</option>";

    $out .= '</select>
        </p>
        <p>
            <label for="xsltrss-template-' . $number . '">'
                . _('Template') . ':</label> <a href="themes.php?page=xsltrss_options"
                >[Edit Templates]</a>
            <select id="xsltrss-template-' . $number . '" class="widefat"
                name="widget-xsltrss[' . $number . '][template]">';

    foreach ($templates as $topt)
        $out .= '<option value="' . $topt . '"' . ($template == $topt
            ? ' selected="selected"'
            : ''
            )
            . ">$topt</option>";

    $out .= '</select>
        </p>';

    echo $out;

}

function jp_widget_xsltrss_process($widget_rss, $check_feed = true)
{

	$items = (int)$widget_rss['items'];
	if ( $items < 1 || 20 < $items )
		$items = 10;

    $maxage = (int)$widget_rss['maxage'];
    if ($maxage < 120)
        $maxage = 120;

	$url        = sanitize_url(strip_tags($widget_rss['url']));
	$title      = trim(strip_tags($widget_rss['title']));
    $link       = sanitize_url(strip_tags($widget_rss['link']));
    $template   = trim(strip_tags($widget_rss['template']));

	if ($check_feed) {
		require_once(ABSPATH . WPINC . '/rss.php');
		$rss = fetch_rss($url);
		$error = false;
		if (!is_object($rss)) {
			$url = wp_specialchars(__('Error: could not find an RSS or ATOM feed at that URL.'), 1);
			$error = sprintf(__('Error in RSS %1$d'), $widget_number);
		}
	}

	return compact('title', 'url', 'link', 'items', 'error', 'template', 'maxage');

}

function jp_widget_xsltrss_options_page()
{

    $file = (isset($_GET['file'])
        ? preg_replace('/[^a-z0-9.\-_]/', '', (string)$_GET['file'])
        : null
        );

    $out = '<div class="wrap">
        <!--h2>Add, Edit &amp; Delete XSL Templates</h2-->';

    $out .= jp_widget_xsltrss_checkcache();

    $out .= jp_widget_xsltrss_checkenvironment();

    if (!is_writable(JP_WIDGET_XSLTRSS_XSL))
        $out .= '<div class="error"><p><strong>ERROR:</strong> Please modify the
            xsl templates folder (' . JP_WIDGET_XSLTRSS_XSL . ') to enable write
            access.</p></div>';

    if (isset($_GET['clearcache']))
        $out .= jp_widget_xsltrss_clearcache();

    if ($file) {

        $out .= jp_widget_xsltrss_editfile($file);

    } else {

        if (isset($_GET['create']))
            $out .= jp_widget_xsltrss_createfile();

        $out .= jp_widget_xsltrss_listfiles();

    }

    $out .= '</div>';

    echo $out;

}

function jp_widget_xsltrss_checkenvironment()
{

    /**
     * Check PHP version
     */
    if (phpversion() < 5)
        return '<div class="error"><p><strong>ERROR:</strong> This plugin will
            only work on systems with a PHP version greater than 5, please
            contact your system administrator.</p></div>';

    /**
     * Check for fopen_wrappers
     */
    if (!ini_get('allow_url_fopen'))
        return '<div class="error"><p><strong>ERROR:</strong> Your PHP
            enviroment must support fopen_wrappers to use this plugin, please
            contact your system administrator.</p></div>';

    /**
     * Check for DOM library
     */
    if (!class_exists('DOMDocument'))
        return '<div class="error"><p><strong>ERROR:</strong> Your PHP
            enviroment must have the DOM extention library installed.</p></div>';

    /**
     * Check for xslt library
     */
    if (!class_exists('XSLTProcessor'))
        return '<div class="error"><p><strong>ERROR:</strong> Your PHP
            enviroment must have the XSL extention library installed.</p></div>';

    return '';

}

function jp_widget_xsltrss_checkcache()
{

    if (!file_exists(JP_WIDGET_XSLTRSS_CACHE)) {

        if (is_writable(WP_CONTENT_DIR)) {

            mkdir(JP_WIDGET_XSLTRSS_CACHE);

            return '';

        } else {

            return '<div class="error"><p><strong>ERROR:</strong> Please
                create a writable folder named &quot;cache&quot; in your
                wp-content folder (' . WP_CONTENT_DIR . ').</p></div>';

        }

    } elseif (!is_writable(JP_WIDGET_XSLTRSS_CACHE)) {

        return '<div class="error"><p><strong>ERROR:</strong> Please modify the
            cache folder (' . JP_WIDGET_XSLTRSS_CACHE . ') to enable write
            access.</p></div>';

    }

}

function jp_widget_xsltrss_clearcache()
{

    if (!file_exists(JP_WIDGET_XSLTRSS_CACHE))
        return;

    $d = dir(JP_WIDGET_XSLTRSS_CACHE);
    while (false !== ($file = $d->read()))
        if(substr($file, strrpos($file,'_') + 1) == 'xsltrss.xml')
            unlink(JP_WIDGET_XSLTRSS_CACHE . DIRECTORY_SEPARATOR . $file);

    $d->close();

    return '<div id="message" class="updated fade"><p>Cache deleted.</p></div>';

}

function jp_widget_xsltrss_createfile()
{

    $file = (isset($_GET['create'])
        ? preg_replace('/[^a-z0-9.\-_]/', '', (string)$_GET['create'])
        : null
        );

    if (!strrpos($file,'.'))
        $file .= '.xsl';

    $filepath = JP_WIDGET_XSLTRSS_XSL . DIRECTORY_SEPARATOR . $file;

    if (file_exists($filepath))
        return '<p><strong>The file already exists!</strong></p>';

    if (!$file)
        return '<p><strong>Invalid file name</strong></p>';

    if (!is_writable(JP_WIDGET_XSLTRSS_XSL))
        return '<p><strong>XSL folder is not writable!</strong></p>';

    if (substr($file, strrpos($file, '.') + 1) != 'xsl')
        return '<p><strong>You may only create XSL files!</strong></p>';

    $content = <<<EOF
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:php="http://php.net/xsl"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xsl:extension-element-prefixes="php"
    >

    <xsl:output
        method="html"
        indent="yes"
        encoding="iso-8859-1"
        />

    <xsl:template match="/rss/channel">

        <xsl:value-of select="\$before_widget" disable-output-escaping="yes" />
        <xsl:value-of select="\$before_title" disable-output-escaping="yes" />

        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="\$url"/>
            </xsl:attribute>
            <xsl:attribute name="class">
                <xsl:text>rsswidget</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:text>Syndicate this content</xsl:text>
            </xsl:attribute>
            <xsl:element name="img">
                <xsl:attribute name="style">
                    <xsl:text>background:orange;color:white;border:none;</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="height">
                    <xsl:text>14</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="width">
                    <xsl:text>14</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="src">
                    <xsl:copy-of select="\$rss_icon"/>
                </xsl:attribute>
            </xsl:element>
        </xsl:element>

        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:copy-of select="link"/>
            </xsl:attribute>
            <xsl:attribute name="class">
                <xsl:text>rsswidget</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:copy-of select="description" />
            </xsl:attribute>
            <xsl:value-of select="title" />
        </xsl:element>
        <xsl:value-of select="\$after_title" disable-output-escaping="yes" />

        <ul>
            <xsl:for-each select="item">

                <xsl:if test="position() &lt;= \$items">
                <li>
                    <xsl:element name="a">
                        <xsl:attribute name="href">
                            <xsl:value-of select="link" />
                        </xsl:attribute>
                        <xsl:attribute name="class">
                                <xsl:text>rsswidget</xsl:text>
                        </xsl:attribute>
                        <xsl:attribute name="title">
                                <xsl:value-of select="description" />
                        </xsl:attribute>
                        <xsl:value-of select="title" />
                    </xsl:element>
                </li>
                </xsl:if>

            </xsl:for-each>
        </ul>

        <xsl:value-of select="\$after_widget" disable-output-escaping="yes" />

    </xsl:template>

</xsl:stylesheet>
EOF;

    file_put_contents($filepath, $content);

    return '<div id="message" class="updated fade"><p>Created xsl file.</p></div>';

}

function jp_widget_xsltrss_editfile($file)
{

    $filepath = JP_WIDGET_XSLTRSS_XSL . DIRECTORY_SEPARATOR . $file;
    $out = '';

    if (substr($filepath, strrpos($filepath, '.') + 1) != 'xsl')
        return 'Only allowed to edit xsl files.';

    if (!file_exists($filepath))
        return 'File, &quot;' . $file . '&quot; doesn\'t exist!';

    if (!is_readable($filepath))
        return 'File, &quot;' . $file . '&quot; is not readable!';

    if (isset($_GET['delete'])) {

        unlink($filepath);

        return '<div id="message" class="updated fade"><p>Deleted XSL file.</p></div>'
            . jp_widget_xsltrss_listfiles();

    }

    if (!empty($_POST)
        && isset($_POST['file-content'])
        ) {

        $filecontent = (get_magic_quotes_gpc()
            ? stripslashes((string)$_POST['file-content'])
            : (string)$_POST['file-content']
            );

        file_put_contents($filepath, $filecontent);

        $out .= '<div id="message" class="updated fade"><p>File edited successfully.</p></div>';

    }

    $out .= '<h2>Edit XSL Template</h2>
        <div class="tablenav">
            <big><strong>' . $file . '</strong></big>
        </div>

        <div>&nbsp;</div>

        <form method="POST"
            action="themes.php?page=xsltrss_options&amp;file=' . $file . '">
        <textarea cols="120" rows="25" tabindex="1" name="file-content">'
        . htmlentities(file_get_contents($filepath))
        . '</textarea>
        <p class="submit">
            <input type="submit" name="submit" value="Update File" tabindex="2" />
        </p>
        </form>
        <h3>Tips</h3>
        <p>Ensure you use the before_widget, before_title, after_title and
            after_widget variables in your template. You must use xsl:value-of
            with dissable-output-excaping set to yes. I.E.
        </p><code>&lt;xsl:value-of select=&quot;$before_widget&quot; disable-output-escaping=&quot;yes&quot; /&gt;</code>
        <p>
            Additionally if you want to enable the item limiting feature your
            tempalte will have to respond to the $items variable. The feed
            itself is not modified by this setting. To do so you should use a
            conditional immediatly following your for-each tag. I.E.
        </p>
        <pre>
&lt;xsl:for-each select=&quot;item&quot;&gt;
    &lt;xsl:if test=&quot;position() &amp;lt;= $items&quot;&gt;
        &lt;!-- each iteration here --&gt;
    &lt;/xsl:if&gt;
&lt;/xsl:for-each&gt;
        </pre>
        <p>
            If you want to add a title and description to your template ensure
            that you include the xsltrss namespace otherwise you\'ll get nasty
            &quot;Namespace prefix xsltrss on [tag] is not defined&quot; errors.
            To include the namespace simply add the following line to your
            &lt;xsl:stylesheet&gt; element:
        </p>
        <pre>
xmlns:xsltrss="http://jp.jixor.com/plugins/xslt-rss"
        </pre>';

    return $out;

}

function jp_widget_xsltrss_listfiles()
{

    $base_uri = 'themes.php?page=xsltrss_options';

    $templates = jp_widget_xsltrss_getfiles();

        $dom = new DOMDocument;

    $out = '<h2>Manage XSL Templates</h2>
        <ul>';

    foreach($templates as $t) {

        $dom->load(JP_WIDGET_XSLTRSS_XSL . DIRECTORY_SEPARATOR . $t);

        $title = $dom->getElementsByTagNameNS(
            'http://jp.jixor.com/plugins/xslt-rss',
            'title'
            )->item(0)->nodeValue;

        /**
         * If a title was displayed in the xsl file then append the file name,
         * otherwise simply make the file name the title.
         */
        $title = (!empty($title)
            ? $title . ' (' . $t . ')'
            : $t
            );

        $description = $dom->getElementsByTagNameNS(
            'http://jp.jixor.com/plugins/xslt-rss',
            'description'
            )->item(0)->nodeValue;

        if (!$title)
            $title = $t;

        $out .= '<li><a href="' . $base_uri . '&amp;file=' . $t . '">' . $title
            . '</a>'
            . (!empty($description)
                ? ' - ' . $description
                : ''
                )
            . ' <small>(<a href="' . $base_uri . '&amp;file=' . $t
            . '&amp;delete=true" onclick="javascript:return window.confirm(
                \'Delete &quot;' . $t . '&quot;?\');">delete</a>)</small></li>';

    }

    $out .= '</ul>
        <p><strong>Tip:</strong> If you want to edit a default template
            first copy to a new file and edit the new copy only. This is so that
            your changes are not lost when you update the plugin.
        </p>
        <h2>Create New Template</h2>
        <form method="GET" action="themes.php">
        <table class="form-table"><tbody>
            <tr class="form-field form-required">
    			<th scope="row" valign="top"><label for="form-create">Template File Name</label></th>
                <td><input type="text" id="form-create" name="create" tabindex="1" />
                    <br />\'.xsl\' extention added automatically.</td>
            </tr>
        </tbody></table>
        <input type="hidden" name="page" value="xsltrss_options" />
        <p class="submit">
            <input type="submit" name="submit" value="Create File" tabindex="2" />
        </p>
        </form>
        <h2>Clear Cache</h2>
        <p>If you change your Wordpress theme you may need to clear the output
            cache.
            <a href="' . $base_uri . '&amp;clearcache=true"><strong>[Clear Cache]</strong></a></p>';

    return $out;

}

function jp_widget_xsltrss_add_page()
{

    add_theme_page('XSLT RSS', 'XSLT RSS', 8, 'xsltrss_options', 'jp_widget_xsltrss_options_page');

}

function jp_widget_xsltrss_init()
{

    if (!$options = get_option('jp_widget_xsltrss'))
		$options = array();

	$widget_ops = array(
        'classname' => 'widget_xsltrss',
        'description' => __('Entries from any RSS or Atom feed')
        );

    $control_ops = array(
        'width' => 400,
        'height' => 300,
        'id_base' => 'xsltrss'
        );

	$registered = false;
	foreach (array_keys($options) as $o) {
		/**
         * Create each instance with its own id in the format "{$id_base}-{$o}"
         */
		$id = "xsltrss-$o";
		//$registered = true; // -- Confising, really the item should always be available to add
		wp_register_sidebar_widget(
            $id,
            'XSLT RSS',
            'jp_widget_xsltrss',
            $widget_ops,
            array('number' => $o)
            );
		wp_register_widget_control(
            $id,
            'XSLT RSS',
            'jp_widget_xsltrss_control',
            $control_ops,
            array('number' => $o)
            );
	}

    /**
     * If there are none, we register the widget's existance with a generic
     * template.
     */
	if ( !$registered ) {
		wp_register_sidebar_widget(
            'xsltrss-1',
            'XSLT RSS',
            'jp_widget_xsltrss',
            $widget_ops,
            array('number' => -1)
            );
		wp_register_widget_control(
            'xsltrss-1',
            'XSLT RSS',
            'jp_widget_xsltrss_control',
            $control_ops,
            array('number' => -1)
            );
	}

}

function jp_xslt_isodate($date, $format = 'Y/m/d')
{

    return date($format, strtotime($date));

}

add_action('widgets_init', 'jp_widget_xsltrss_init');
add_action('admin_menu', 'jp_widget_xsltrss_add_page');
