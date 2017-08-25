<?php
namespace Zodream\Domain\Spider;

use Zodream\Infrastructure\ObjectExpand\JsonExpand;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;

class Html {
    protected $data;

    public function __construct($html) {
        $this->data = $html;
    }

    public function getDocument() {
        $doc = new \DOMDocument();
        $doc->loadHTML($this->data);
        return $doc;
    }

    public function getXPath() {
        return new \DOMXPath($this->getDocument());
    }

    public function getNode($tag) {
        $matches = array();
        preg_match_all("#<>#i", $this->data, $matches);
    }

    public function getLinks() {
        preg_match_all("'<\s*a\s.*?href\s*=\s*			# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx", $this->data, $links);

        while (list($key, $val) = each($links[2])) {
            if (!empty($val))
                $match[] = $val;
        }

        while (list($key, $val) = each($links[3])) {
            if (!empty($val))
                $match[] = $val;
        }
        return $match;
    }

    public function expandLinks($links, $URI) {
        preg_match("/^[^\?]+/", $URI, $match);

        $match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|", "", $match[0]);
        $match = preg_replace("|/$|", "", $match);
        $match_part = parse_url($match);
        $match_root =
            $match_part["scheme"] . "://" . $match_part["host"];

        $search = array("|^http://" . preg_quote($this->host) . "|i",
            "|^(\/)|i",
            "|^(?!http://)(?!mailto:)|i",
            "|/\./|",
            "|/[^\/]+/\.\./|"
        );

        $replace = array("",
            $match_root . "/",
            $match . "/",
            "/",
            "/"
        );

        $expandedLinks = preg_replace($search, $replace, $links);

        return $expandedLinks;
    }
}