<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en_US" lang="en_US">
<head>
<style type="text/css">
body {
    background-color: #E0E0E0;
    font-size: 14px;
    font-family: arial, sans-serif;
    margin: 0px;
    padding: 0px;
    line-height: 1.5em;
}

#container {
    background-color: white;
    width: 768px;
    margin: 0px auto;
    border: 1px black solid;
}

#message {
    background-color: #d1d1d1;
    font-weight: bold;
    padding: 1px 4px;
}

#content {
    padding: 8px;
    border-top: 1px black solid;
}

label {
    font-weight: bold;
}

p {
    margin-bottom: 1.2em;
}

img {
    display: block;
}

h1 {
    font-size: 1.3em;
    border-bottom: 2px dotted black;
}

h2 {
    font-size: 1.2em;
}

#main-text {
    background-color: #E0E0E0;
    padding: 5px;
}

.error {
    font-weight: bold;
    color: red;
}
</style>
</head>
<body>
<div id="container"><img src="./images/autumn_leaf_color.jpg" />
<div id="content"><!-- BEGIN message -->
<div id="message">{MESSAGE}</div>
<!-- END message -->
<h1>{TITLE}</h1>
{CONTENT}</div>
</div>
</body>
</html>
