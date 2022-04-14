<?php  

 header("X-Frame-Options: allow-from https://work.epa.gov");
 //1=Final, 2=Superseded, 3=Draft, 4=Deleted

 $page_type = isset($_GET['p']) ? $_GET['p'] : 0;

 include 'db_connection.php';
 $conn = OpenCon();
 
 //all columns from the record_schedule table
 $query_rs ="SELECT DISTINCT(Schedule_Number) AS Schedule_Number, Schedule_Title, Function_Code, Function_Title, Program, Applicability, NARA_Disposal_Authority_Record_Schedule_Level, Schedule_Description, Disposition_Instructions, Guidance, Status, Custodians, Reasons_For_Disposition, Related_Schedules, DATE_FORMAT(`Entry_Date`,'%m/%d/%Y') as Entry_Date, Previous_NARA_Disposal_Authority, DATE_FORMAT(`EPA_Approval`,'%m/%d/%Y') as EPA_Approval, NARA_Approval, DATE_FORMAT(`Revised_Date`,'%m/%d/%Y') as Revised_Date, Item_Number
 FROM " . $wpdb->prefix . "epa_record_schedule 
 WHERE Reserved_Flag = 0 AND id != '-99999' AND Schedule_Number = ". $_GET["rs"] . " LIMIT 1";  
 $result_main = mysqli_query($conn, $query_rs);

 $query_title ="SELECT DISTINCT(Schedule_Number) AS Schedule_Number, Schedule_Title
 FROM " . $wpdb->prefix . "epa_record_schedule 
 WHERE Reserved_Flag = 0 AND id != '-99999' AND Schedule_Number = ". $_GET["rs"] . " LIMIT 1";  
 $title_main = mysqli_query($conn, $query_title);
 
 $query_di ="SELECT Disposition_Summary FROM " . $wpdb->prefix . "epa_record_schedule WHERE Schedule_Number = ". $_GET["rs"];  
 $result_di = mysqli_query($conn, $query_di);  

if ($page_type != 0) {
 ?>  

<!DOCTYPE html>
<html lang="en" dir="ltr" prefix="content: http://purl.org/rss/1.0/modules/content/  dc: http://purl.org/dc/terms/  foaf: http://xmlns.com/foaf/0.1/  og: http://ogp.me/ns#  rdfs: http://www.w3.org/2000/01/rdf-schema#  schema: http://schema.org/  sioc: http://rdfs.org/sioc/ns#  sioct: http://rdfs.org/sioc/types#  skos: http://www.w3.org/2004/02/skos/core#  xsd: http://www.w3.org/2001/XMLSchema# ">
  <head>
    <meta charset="utf-8" />
<title>
<?php
if ($page_type == 1) {
echo 'EPA Records Schedules | EPA@Work';
} elseif ($page_type == 2) {
echo 'EPA Superseded Records Schedules | EPA@Work';
} elseif ($page_type == 3) {
echo 'EPA Draft Records Schedules | EPA@Work';
}
?>
</title>
<meta name="description" content="A records schedule provides mandatory instructions on how long to keep records (retention) and when records can be destroyed and/or transferred to alternate storage facilities (disposition). Records schedules are also known as records disposition schedules, records retention schedules and records control schedules. />
<meta name="Generator" content="Drupal 9 (https://www.drupal.org)" />
<meta name="MobileOptimized" content="width" />
<meta name="HandheldFriendly" content="true" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" media="all" href="css/css_byo1FQLDeOG9WDRwTYOaTXtK_bqcT1zXkTyZGwzganc.css" />
<link rel="stylesheet" media="all" href="css/css_Hi_QMbLUpIApCCkvMVpWsQ2oAlWkOu7PLTFdcOTODAI.css" />


<script src="js/js_QHqjxhGPGgZFwOfW92tmrVpssmC1sbO0zDG4TgLmaEI.js"></script>
<script src="https://use.fontawesome.com/releases/v5.13.1/js/all.js" defer crossorigin="anonymous"></script>
<script src="https://use.fontawesome.com/releases/v5.13.1/js/v4-shims.js" defer crossorigin="anonymous"></script>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>  
<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />-->
<link rel="stylesheet" media="all" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" />
<link rel="stylesheet" media="all" href="css/css_pv-IXqjlnhjuNCg70IcS449KX8UO9e8-mmP8zWKlzpE.css" />
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<!-- <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>     
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />   -->

<style>
.usa-nav__secondary{margin:-45px;}
</style>
</head>
  
  <body class="path-node page-node-type-landing-page">
        <a href="#main-content" class="visually-hidden focusable skip-link">
      Skip to main content
    </a>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W55ZK7" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
      <div class="dialog-off-canvas-main-canvas" data-off-canvas-main-canvas>
    
    
<div class="usa-overlay"></div>


<header class="usa-header usa-header--extended" role="banner">
      <div class="usa-navbar">
        <div class="region region-header">
    <div class="usa-logo" id="logo">

    <a class="logo-img" href="https://work.epa.gov/" accesskey="1" title="Home" aria-label="Home">
    <img src="img/epa-at-work.png" alt="Home" />
  </a>
    
</div>

  </div>


    <nav class="usa-nav" role="navigation">
      <div class="usa-nav__secondary">
           <div class="region region-secondary-menu">
 
<ul class="usa-nav__secondary-links">
  <li class="usa-nav__secondary-item">
    <a href="https://cfint.rtpnc.epa.gov/locator/extended_search.cfm">
      EPA Locator
    </a>
  </li>
  <li class="usa-nav__secondary-item">
    <a href="https://fs.epa.gov/adfs/ls/?wa=wsignin1.0&amp;wtrealm=urn%3Afederation%3AMicrosoftOnline&amp;wctx=wa%3Dwsignin1%252E0%26rpsnv%3D3%26ct%3D1395925912%26rver%3D6%252E1%252E6206%252E0%26wp%3DMBI%26wreply%3Dhttps%253A%252F%252Fusepa%252Esharepoint%252Ecom">
      My Workplace
    </a>
  </li>
  <li class="usa-nav__secondary-item">
    <a href="https://ppl.epa.gov/psp/ots92prd/?cmd=login">
      PeoplePlus
    </a>
  </li>
  <li class="usa-nav__secondary-item">
    <a href="https://work.epa.gov/saml_login?destination=/node/1">
      Log in
    </a>
  </li>
</ul>


  </div>

            <form action="https://intrasearch.epa.gov/epasearch" class="usa-search usa-search--small"
                  method="GET" data-drupal-form-fields="extended-mega-search-field-small">
              <div role="search"><label class="usa-sr-only" for="extended-mega-search-field-small">Search
                  small</label> <input name="typeofsearch" type="hidden" value="epa"> <input
                        name="result_template" type="hidden" value="agcyintr_default.xsl">
                <input name="areasidebar" type="hidden" value="epaatwork_sidebar"> <input class="usa-input"
                                                                                          id="extended-mega-search-field-small"
                                                                                          name="querytext"
                                                                                          type="search">
                <button class="usa-button" type="submit"><span class="usa-sr-only">Search</span>
                </button>
              </div>
            </form>
          </div>
        
              </div>
          </nav>

    
</header>

<main class="usa-section">
  <div class="grid-container">
    <div class="grid-row grid-gap">
      <div class="grid-col-12">
          <div class="region region-breadcrumb">
    <div id="block-epa-intranet-breadcrumbs" data-block-plugin-id="system_breadcrumb_block" class="block block-system block-system-breadcrumb-block">
  
    
    <h2 id="system-breadcrumb" class="visually-hidden">Breadcrumb</h2>
<ol class="add-list-reset uswds-breadcrumbs uswds-horizontal-list">
  <li>
          <a href="https://work.epa.gov/">EPA@Work Home</a>
      </li>
  <li>
          <a href="https://work.epa.gov/records-management">Records Management</a>
      </li>
  <li>
          <a href="https://patt.epa.gov/app/mu-plugins/pattracking/includes/admin/pages/record_schedule/">EPA Records Schedules</a>
      </li>
  <li>
  <?php

while($row_breadcrumb = mysqli_fetch_array($title_main))  
  {  
   echo $row_breadcrumb["Schedule_Title"];
}
      ?>
      </li>
</ol>

  </div>


  </div>

          <div class="region region-highlighted">
    <div data-drupal-messages-fallback class="hidden"></div>

  </div>


        
      </div>
    </div>

    <div class="grid-row grid-gap">

      
      <div class="region-content tablet:grid-col-12">
          <div class="region region-content">
    <div id="block-epa-intranet-content" data-block-plugin-id="system_main_block" class="block block-system block-system-main-block">
  
    
      <div class="views-element-container"><div class="view view-mass-mailers view-id-mass_mailers view-display-id-page_1 js-view-dom-id-e1386f114016194050874f8e478fd8ffd15730aaf22446fc444969a212213320">
  
      <?php
if ($page_type == 1) {
echo '<div class="alert alert-info" role="alert">Final schedules are approved by the National Archives and Records Administration (NARA). Browse the final schedules below. Consolidated schedules that are still waiting for NARA approval can be found on the superseded schedules page.</div>';
} elseif ($page_type == 2) {
echo '<div class="alert alert-info" role="alert"><strong>This schedule is superseded by a consolidated schedule; to know which consolidated schedule, refer to superseded schedules. It may not be used to retire or destroy records. If you have any questions, please contact the Records Help Desk.</strong></div>';
} elseif ($page_type == 3) {
echo '<div class="alert alert-info" role="alert"><strong>This schedule is in draft. It may not be used to retire or destroy records. If you have any questions, please contact the Records Help Desk.</strong></div>';
}
      echo($query);
while($row_main = mysqli_fetch_array($result_main))  
  {  
   echo '
   <div class="view-header"><h1>EPA Records Schedule '.$row_main["Schedule_Number"].'</h1></div>
   <div class="container">
   <strong>Status: </strong>'.$row_main["Status"].', '. $row_main["Revised_Date"] . '<br />
   <strong>Title: </strong>'.$row_main["Schedule_Title"].'<br />  
   <strong>Program: </strong>'.$row_main["Program"].'<br />   
   <strong>Applicability: </strong>'.$row_main["Applicability"].'<br />';
   if(!empty($row_main["Function_Title"])) {
     echo '<strong>Function: </strong>'.$row_main["Function_Code"].' - '.$row_main["Function_Title"];
   }
   else {
     echo '<strong>Function: </strong>'.$row_main["Function_Code"];
   }
   
   if(!empty($row_main["NARA_Disposal_Authority_Record_Schedule_Level"])) {
        echo'<p><strong>NARA Disposal Authority:</strong></p>'.$row_main["NARA_Disposal_Authority_Record_Schedule_Level"];
   }
   
   if(!empty($row_main["Schedule_Description"])) {
        echo '<p><strong>Description:</strong></p>'.$row_main["Schedule_Description"];
   }
   if(!empty($disposition_summary["Disposition_Summary"])) {
       echo '<strong>Disposition Instructions:</strong><br/>';
       while($disposition_summary = mysqli_fetch_array($result_di)) {
            echo $disposition_summary["Disposition_Summary"];
        }
   }
   
   if(!empty($row_main["Guidance"])) {
        echo '<strong>Gudiance:</strong><br />'.$row_main["Guidance"].'</br>';
   }
   
   if(!empty($row_main["Reasons_For_Disposition"])) {
        echo '<p><strong>Reasons for Disposition: </strong></p>'.$row_main["Reasons_For_Disposition"].'</br>';
   }
   
   echo'
   <strong>Custodians: </strong>'.$row_main["Custodians"].'</br>
   <strong>Related Schedules: </strong>'.$row_main["Related_Schedules"].'</br>
   <strong>Previous NARA Disposal Authority: </strong>'.$row_main["Previous_NARA_Disposal_Authority"].'</br>
   <strong>Entry: </strong>'.$row_main["Entry_Date"].'</br>';
   if($row_main["EPA_Approval"] == '00/00/0000') {
        echo '<strong>EPA Approval: </strong>Not Applicable</br>';
   }
   else {
        echo '<strong>EPA Approval: </strong>'.$row_main["EPA_Approval"].'</br>';
   }
   echo '<strong>NARA Approval: </strong>'.$row_main["NARA_Approval"].'</br>';
}


      ?>
      
</main>          
</div>
<footer class="usa-footer usa-footer--slim" role="contentinfo">
  <div class="grid-container usa-footer__return-to-top">
          <span class="align-right">
        <!--Last updated: -->
      </span>
      </div>
        <div class="usa-footer__primary-section">
    <div class="usa-footer__primary-container grid-row">
              <div class="mobile-lg:grid-col-12">
          <nav class="usa-footer__nav" aria-label="Footer navigation">
              <div class="region region-footer-menu">
    

    
                <ul class="grid-row grid-gap add-list-reset">
        
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://www.epa.gov/" title="EPA.gov" class="usa-footer__primary-link">EPA.gov</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/accessibility" title="Accessibilty" class="usa-footer__primary-link" data-drupal-link-system-path="node/4639">Accessibility</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/calendar" class="usa-footer__primary-link" data-drupal-link-system-path="calendar">Calendar</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/news" title="News" class="usa-footer__primary-link" data-drupal-link-system-path="news">News</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/epa/notice-perceived-unaddressed-significant-public-health-or-environmental-risk" class="usa-footer__primary-link" data-drupal-link-system-path="node/3682">Report an Issue</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/epa/whistleblower-protection" class="usa-footer__primary-link" data-drupal-link-system-path="node/327">Whistleblower Protection</a>
      </li>
      
    
                  <li class="mobile-lg:grid-col-6 desktop:grid-col-auto usa-footer__primary-content">
        <a href="https://work.epa.gov/form/contact" class="usa-footer__primary-link" data-drupal-link-system-path="webform/contact">Contact EPA@Work</a>
      </li>
      
    
                </ul>
        
  



  </div>

          </nav>
        </div>
                </div>                                                                                                                             
  </div>
  <div class="usa-footer__secondary-section">
    <div class="grid-container">
              <div class="usa-footer__logo grid-row grid-gap-2">
                      <div class="grid-col-auto">
              <img class="usa-footer__logo-img" src="img/logo.svg" alt="Environmental Protection Agency (EPA) logo">
            </div>
                                <div class="grid-col-auto">
              <p class="usa-footer__logo-heading">Environmental Protection Agency (EPA)</p>
            </div>
                  </div>
                </div>
  </div>
  </footer>


  </div>

    


  </body>
</html>
<?php
} else {
echo 'ERROR: Page Type Missing';
}
?>