<?php


i18n_merge('EasyComments') || i18n_merge('EasyComments', 'en_US');
# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
    $thisfile, //Plugin id
    'EasyComments',     //Plugin name
    '1.0',         //Plugin version
    'Multicolor',  //Plugin author
    'http://bit.ly/donate-multicolor-plugins', //author website
    i18n_r('EasyComments/DESC'), //Plugin description
    'plugins', //page type - on which admin tab to display
    'BackendEasyComments'  //main function (administration)
);



$fileLog = GSDATAOTHERPATH . 'easyCommentsLog.txt';


# add a link in the admin tab 'theme'
add_action('plugins-sidebar', 'createSideMenu', array($thisfile, 'EasyComments Info🤭'));


# functions
function BackendEasyComments()
{
    echo '<div style="width:100%;background:#fafafa;border:solid 1px #ddd;padding:10px;box-sizing:border-box;">';
    echo i18n('EasyComments/PLACE') . " <code style='background:#000;padding:5px;margin:0 5px;color:#FAFA33;font-weight:bold;'>&lt;?php easyComments();?&gt;</code> <br><br>";

    include(GSPLUGINPATH . 'easyComments/backform.inc.php');

    echo "<script type='text/javascript' src='https://storage.ko-fi.com/cdn/widget/Widget_2.js'></script><script type='text/javascript'>kofiwidget2.init('Support Me on Ko-fi', '#29abe0', 'I3I2RHQZS');kofiwidget2.draw();</script> </div>";


    if (isset($_POST['deletelog'])) {

        global $fileLog;
        unlink($fileLog);
        echo "<meta http-equiv='refresh' content='0'>";
    };
}


function easyComments()
{

    if (nm_post_slug(false) !== null) {

        $id = nm_post_slug(false);
    } else {
        $id = get_page_slug($echo = false);
    };


    $fileDir = GSDATAOTHERPATH . 'easyComments/' . $id . '.xml';

    if (!isset($_SESSION)) {
        session_start();
    };


    if (isset($_POST['sendcomment'])) {
        // Sprawdź, czy kod CAPTCHA został wprowadzony poprawnie
        if (isset($_POST['captcha_answer']) && $_POST['captcha_answer'] == $_SESSION['captcha_question']) {
            if (!empty($_POST['honeypot'])) {
                echo 'Błędne żądanie!';
                exit;
            }


            // Pobranie danych z formularza


            $name = htmlentities($_POST['name']);
            $email = htmlentities($_POST['email']);
            $message = htmlentities($_POST['message']);
            $parent_id = $_POST['parent_id'] !== '' ? htmlentities($_POST['parent_id']) : null;

            // Otwarcie pliku XML
            if (file_exists($fileDir)) {
                $xml = simplexml_load_file($fileDir);
            } else {

                if (!file_exists(GSDATAOTHERPATH . 'easyComments/')) {
                    mkdir(GSDATAOTHERPATH . 'easyComments/', 0755);
                };

                file_put_contents(GSDATAOTHERPATH . 'easyComments/.htaccess', 'Deny from all');
                $con = '<?xml version="1.0"?><comments></comments>';
                file_put_contents($fileDir, $con);
                $xml = simplexml_load_file($fileDir);
            };


            // Jeśli istnieje parent_id, dodaj odpowiedź
            if ($parent_id !== null) {
                $parentComment = $xml->xpath("//comment[@id='$parent_id']")[0];
                $response = $parentComment->addChild('response');
                $response->addChild('name', $name);
                $response->addAttribute('id', md5(uniqid('', true)));
                $response->addChild('email', $email);
                $response->addChild('message', strip_tags(html_entity_decode($message)));
            } else {
                $comment = $xml->addChild('comment');
                $comment->addAttribute('id', uniqid());
                $comment->addChild('name', $name);
                $comment->addChild('email', $email);
                $comment->addChild('message',  strip_tags(html_entity_decode($message)));
            }

            // Zapisanie zmian w pliku XML
            $xml->asXML($fileDir);

            global $fileLog;
            global $id;
            if (file_exists($fileLog)) {
                file_put_contents($fileLog,  ' <b>' . date('l jS \of F Y h:i:s A')  .' '. i18n_r('EasyComments/COMMENTWAIT') . $id . '</b><br>' . file_get_contents($fileLog));
            } else {
                file_put_contents($fileLog, ' <b>' . date('l jS \of F Y h:i:s A') .' '. i18n_r('EasyComments/COMMENTWAIT') . $id . '</b><br>');
            };

            echo '<div class="alert alert-success" id="comment-alert"><span>Comments Added! Please wait for accept from administrator</span></div>';
            echo "<meta http-equiv='refresh' content='1'>";
        } else {
            // Kod CAPTCHA jest niepoprawny
            echo '<div class="alert alert-success" id="comment-alert"><span>Wrong Captcha!</span></div>';
            echo "<meta http-equiv='refresh' content='1'>";
        }

        // Usuń pytanie CAPTCHA z sesji
        unset($_SESSION['captcha_question']);
        unset($_SESSION['captcha_answer']);

        global $fileLog;
        global $id;
    };



    if (isset($_POST['deleteComment'])) {
        $xmlFile = $fileDir;

        // Ładuj plik XML
        $xml = simplexml_load_file($xmlFile);

        // Identyfikator komentarza do usunięcia
        $commentIdToDelete =  $_POST['deleteComment'];

        // Znajdź elementy z odpowiednim id i usuń je
        $elementsToDelete = $xml->xpath("//comment[@id='$commentIdToDelete']");
        foreach ($elementsToDelete as $element) {
            $dom = dom_import_simplexml($element);
            $dom->parentNode->removeChild($dom);
        }

        // Znajdź elementy response z odpowiednim id i usuń je
        $responsesToDelete = $xml->xpath("//response[@id='$commentIdToDelete']");
        foreach ($responsesToDelete as $response) {
            $domResponse = dom_import_simplexml($response);
            $domResponse->parentNode->removeChild($domResponse);
        }


        // Zapisz zmiany do pliku XML
        $xml->asXML($xmlFile);

        echo '<div class="alert alert-success" id="comment-alert"><span>Comment Deleted!</span></div>';
        echo "<meta http-equiv='refresh' content='1'>";
    }

    if (isset($_POST['publishComment'])) {
        $xmlFile = $fileDir;

        // Ładuj plik XML
        $xml = simplexml_load_file($xmlFile);

        // Identyfikator komentarza do opublikowania
        $commentIdToPublish = $_POST['publishComment'];

        // Znajdź elementy komentarza z odpowiednim id i dodaj element <approved>
        $elementsToPublish = $xml->xpath("//comment[@id='$commentIdToPublish']");
        foreach ($elementsToPublish as $element) {
            $element->addChild('approved');
        }

        // Znajdź elementy response z odpowiednim id i dodaj element <approved>
        $responsesToPublish = $xml->xpath("//response[@id='$commentIdToPublish']");
        foreach ($responsesToPublish as $response) {
            $response->addChild('approved');
        }

        // Zapisz zmiany do pliku XML
        $xml->asXML($xmlFile);

        echo '<div class="alert alert-success" id="comment-alert"><span>Comment published!</span></div>';

        echo "<meta http-equiv='refresh' content='1'>";
    }





    include(GSPLUGINPATH . 'easyComments/loop.inc.php');
    include(GSPLUGINPATH . 'easyComments/captcha.inc.php');
    include(GSPLUGINPATH . 'easyComments/form.inc.php');
};
