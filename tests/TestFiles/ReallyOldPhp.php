<?php
    $first_year=2007;
    if ($first_year==date('Y')) $lastyear=$first_year;
    else $lastyear=$first_year.' - '.date('Y');
    if ($_GET['news'] && is_numeric($_GET['news']))
    {
        header('Location: index.php?what=mainframe&newsid='.$_GET['news']);
        die();
        echo 'cos';
    } else if (isset($_GET['getrss']))
    {
        include('news.php');
        die();
    } else if (isset($_GET['reg']) && $_GET['reg'])
    {
        Header('Location: index.php?what=reg&id='.$_GET['reg']);
        die();
    }

    function user_nav_type()
    {
        $brow = strtolower($_SERVER['HTTP_USER_AGENT']);

        if (strpos($brow,'opera') > 0)
            return 'opera';
        else if (strpos($brow,'netscape') > 0)
            return 'netscape';
        else if (strpos($brow,'msie') > 0)
            return 'ie';
        else
            return false;
    }
?>
<HTML>
<HEAD>
    <META name="author" content="Andrzej 'PsychoB' Budzanowski">
    <link rel="alternate" type="application/rss+xml" title="Get RSS 2.0 Feed" href="index.php?getrss=true" />
    <link rel="shortcut icon" href="favicon.ico" />
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-2" />
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <TITLE> ReCore Software </TITLE>
    <SCRIPT language="JavaScript">
        var Tool = null;

        document.onmousemove=UaktualnijTool;

        function UaktualnijTool(e)
        {
            var x = (document.all) ? event.x + document.body.scrollLeft + 20 : e.pageX + 20;
            var y = (document.all) ? event.y + document.body.scrollTop + 20 : e.pageY + 20;
            if (Tool!=null) {
                if (x < 570) Tool.style.left=x;
                else Tool.style.left=570
                Tool.style.top=y; }
        }

        function ShowTool(id)
        {
            Tool = document.getElementById(id);
            UaktualnijTool;
            Tool.style.visibility = 'visible';
            return true;
        }

        function HideTT()
        {
            Tool.style.visibility = 'hidden';
            Tool = null;
            return true;
        }
        function SprawdzPoprawnosc()
        {
            if (document.wpis.login.value == 'Login')
            {
                alert('Wpisz swój login');
                return false;
            }
            if (document.wpis.elements[2].checked == false)
            {
                alert('Won Spambocie!!')
                return false
            }
            if (document.wpis.tekscik.value=='')
            {
                alert('Wpisz jaki tekst do komentarza')
                return false
            }
            document.wpis.submit();
            return true;
        }
    </SCRIPT>
    <LINK REL="stylesheet" HREF="recore.css" TYPE="text/css">
    <? if (user_nav_type() == 'ie') echo '<LINK REL="stylesheet" HREF="ie_addon.css" TYPE="text/css">'; ?>
</HEAD>
<BODY>
<TABLE id="tabela" align="center">
    <TR>
        <TD id="naglowek">
            <h1 onmouseover="javascript:ShowTool('tt_nfo');" onmouseout="javascript:HideTT();"><sup>Re</sup>Core Software</h1>
        </TD>
    </TR>
    <TR>
        <TD id="links">
            <a href="index.php?what=mainframe" onmouseover="javascript:ShowTool('tt_stronka');" onmouseout="javascript:HideTT();">Strona Główna</a> -
            <a href="index.php?what=projekty" onmouseover="javascript:ShowTool('tt_projekty');" onmouseout="javascript:HideTT();">Projekty</a> -
            <a href="index.php?what=linki" onmouseover="javascript:ShowTool('tt_linki');" onmouseout="javascript:HideTT();">Linki</a> -
            <a href="index.php?what=ksiega" onmouseover="javascript:ShowTool('tt_ksiega');" onmouseout="javascript:HideTT();">Księga gości</a> -
            <a href="index.php?what=autor" onmouseover="javascript:ShowTool('tt_autor');" onmouseout="javascript:HideTT();">Autor</a>
        </TD>
    </TR>
    <TR>
        <TD id="motto">
            <? include ('motto.php'); ?>
        </TD>
    </TR>
    <TR>
        <TD id="main">
            <?
                /*
                 zmiana dokumentu
                */
                if ($_GET['what'])
                {
                    $_GET['what']=strtolower($_GET['what']);
                    if ($_GET['what']=='mainframe') include('news.php');
                    else if ($_GET['what']=='projekty') include('projekty.php');
                    else if ($_GET['what']=='teksty') include('arty.php');
                    else if ($_GET['what']=='ksiega') include('ksiega.php');
                    else if ($_GET['what']=='autor') include('autor.html');
                    else if ($_GET['what']=='reg') include ('reg/reg.php');
                    else if ($_GET['what']=='linki') include ('linki.html');
                    else include('news.php');
                } else {
                    include ('news.php');
                }
            ?>
        </TD>
    </TR>
    <TR>
        <TD id="copy">
            <a href="index.php?getrss">Get RSS 2.0</a><br>
            &copy ReCore SoftWare <? echo $lastyear;?><br>
            Tą stronę najlepjej oglądać pod <a href="http://www.mozilla.com/">Firefoxem</a> 2.0/+ i w rozdzielczości 800 x 600
        </TD>
    </TR>
</TABLE>
<DIV class="tooltip" id="tt_stronka">Tutaj wszelkie newsy ze mojej strony</DIV>
<DIV class="tooltip" id="tt_projekty">Tutaj możesz znaleźć trochę moich projektów, są tutaj i te skończone i te nieskończone</DIV>
<DIV class="tooltip" id="tt_ksiega">Wpisz się i skomentuj stronkę.</DIV>
<DIV class="tooltip" id="tt_autor">Tutaj znajdziesz tego kto popełnił tą stronę.</DIV>
<DIV class="toolnfo" id="tt_nfo">Twoje ip:<a href="http://<? echo $_SERVER["REMOTE_ADDR"]; ?>"><? echo $_SERVER['REMOTE_ADDR']; ?></a><br>Twoja przeglądarka: <? echo $_SERVER["HTTP_USER_AGENT"]; ?></DIV>
<DIV class="tooltip" id="tt_linki">Tutaj znajdują się linki do ciekawych stron.</DIV>
</BODY>
</HTML>
