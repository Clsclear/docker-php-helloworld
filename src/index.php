<?php

if (!isset($_GET['url'])) {
    echo 'No URL parameter';
    exit;
}

$url = $_GET['url'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

$js = <<<EOD
!function() { "use strict"; function e(e) { try { if ("undefined" == typeof console) return; "error"in console ? console.error(e) : console.log(e) } catch (e) {} } function t(e) { return d.innerHTML = '<a href="' + e.replace(/"/g, "&quot;") + '"></a>', d.childNodes[0].getAttribute("href") || "" } function r(e, t) { var r = e.substr(t, 2); return parseInt(r, 16) } function n(n, c) { for (var o = "", a = r(n, c), i = c + 2; i < n.length; i += 2) { var l = r(n, i) ^ a; o += String.fromCharCode(l) } try { o = decodeURIComponent(escape(o)) } catch (u) { e(u) } return t(o) } function c(t) { for (var r = t.querySelectorAll("a"), c = 0; c < r.length; c++) try { var o = r[c] , a = o.href.indexOf(l); a > -1 && (o.href = "mailto:" + n(o.href, a + l.length)) } catch (i) { e(i) } } function o(t) { for (var r = t.querySelectorAll(u), c = 0; c < r.length; c++) try { var o = r[c] , a = o.parentNode , i = o.getAttribute(f); if (i) { var l = n(i, 0) , d = document.createTextNode(l); a.replaceChild(d, o) } } catch (h) { e(h) } } function a(t) { for (var r = t.querySelectorAll("template"), n = 0; n < r.length; n++) try { i(r[n].content) } catch (c) { e(c) } } function i(t) { try { c(t), o(t), a(t) } catch (r) { e(r) } } var l = "/cdn-cgi/l/email-protection#" , u = ".__cf_email__" , f = "data-cfemail" , d = document.createElement("div"); i(document), function() { var e = document.currentScript || document.scripts[document.scripts.length - 1]; e.parentNode.removeChild(e) }() }();
EOD;

$html = str_replace('</body>', "<script>{$js}</script></body>", $html);

function decodeCloudflareEmail($encodedString) {
    $cfKey = substr($encodedString, 0, 2);
    $email = '';
    for ($i = 2; $i < strlen($encodedString); $i += 2) {
        $email .= chr(hexdec(substr($encodedString, $i, 2)) ^ hexdec($cfKey));
    }
    return $email;
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);
$emailNodes = $xpath->query('//a[contains(@class, "__cf_email__")]');

foreach ($emailNodes as $emailNode) {
    $encodedEmail = $emailNode->getAttribute('data-cfemail');
    $decodedEmail = decodeCloudflareEmail($encodedEmail);
    $emailNode->parentNode->replaceChild($dom->createTextNode($decodedEmail), $emailNode);
}

echo $dom->saveHTML();
?>
