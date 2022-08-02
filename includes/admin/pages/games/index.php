<!DOCTYPE html>
<html>
<body>

<h1>ARMS Gamificaton Engine</h1>

<h2>Overview</h2>
<ol>
<li><strong>Create levels.</strong> Levels are acheived when a certain point threshold is met. Points are earned automatically when a event is completed.</li>
<li><strong>Create rewards.</strong> Rewards are badges within the system.</li>
<li><strong>Create events.</strong> Events are actions users have to do like press a button, add a comment, read a document. Each event has a point value assigned.</li>
<li><strong>Add rules.</strong>&nbsp;Rules will dictate how users can earn rewards. For instace a rule can be:&nbsp;<em>A user needs to read 10 documents to gain a badge called "Frequent Reader"</em>&nbsp;or&nbsp;<em>A user needs to gain 1000 points to reach Level 2</em></li>
<li><strong>Send activities</strong>&nbsp;Activities are events that users do in a specific moment.</li>
<li><strong>Done!</strong>&nbsp;Once you start sending activities the app will take care of saving them and checking whether that activity has result in the user winning an award. For instance: If an Event named&nbsp;<em>press button</em>&nbsp;earns the user 1 point and to reach level 1 you need 100 points. When you send 100 activities of a user doing the event, they will obtain the reward.</li>
</ol>

<h2>API</h2>
<p>Before using the API, you must register your application and obtain a API Key.<br /><br />The following endpoints are available:</p>
<h3>Activity [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/activity.php<br /></strong></p>
<p><strong>Required Parameters:<br /><br />employee_id:</strong> (S) EPA Employee ID</p>
<p><strong>lan_id</strong>: (S) LAN ID of the user.</p>
<p><strong>office_code</strong>: (S) AA'ship Office Code</p>
<p><strong>event_id</strong>: (I) See the events table for a complete list of event IDs.</p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<br />
<p><strong>Optional Parameters:<br /><br />bulk_number:</strong> (I) Number of times event should be triggered. Support bulk events.</p>
  
<h3>Get Receiver Data [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/receiver.php</strong></p>
<p><strong>Required Parameters:<br /></strong></p>
<p><strong>employee_id: </strong>(S) EPA Employee ID</p>
<p><strong>*lan_id: </strong>(S) EPA LAN ID *Optional</p>
<p><strong>type</strong>: (S) Available options: profile, badges, office_proximity. Gets information on the user including their points, level, office rank and overall rank. Get badges that have been awarded to the user.</p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<h3>Get Data [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/get_data.php</strong></p>
<p><strong>Required Parameters:<br /></strong></p>
<p><strong>table</strong>: (S) Available options: events, levels, rewards, rules. Gets information on all events, levels, rewards and rules available in the system.</p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<h3>Office Leader Board [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/office_leader_board.php</strong></p>
<p><strong>Required Parameters:<br /></strong></p>
<p><strong>office_code</strong>: (S) AA'ship Office Code. Retrieves all receivers and their office rank for a specific AA'ship specified.</p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<h3>AA Leader Board [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/aa_leader_board.php</strong></p>
<p><strong>Required Parameters:<br /></strong></p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<h3>Overall Leader Board [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/overall_leader_board.php</strong></p>
<p><strong>Required Parameters:</strong></p>
<p><strong>api_key</strong>: (S) API Key issued for the specific application submitting to the gamification engine.</p>
<p>Retrieves all receivers and their overall rank.&nbsp;</p>

<h3>Update Tables [POST]</h3>
<p><strong>/web/app/mu-plugins/pattracking/includes/admin/pages/games/update_tables.php</strong></p>
<h4>All UPDATE statements require the database ID and active will always be set to true by default on INSERT.</h4>
<h4>INSERT/UPDATE to the conditions, events, rewards, and rules tables</h4>
<hr>

<h4>Conditions Table : INSERT</h4>
<p><strong>Required Parameters:</strong></p>
<ul>
<li><strong>rule_id</strong>: (I) Foreign key to the rules table</li>
<li><strong>operation</strong>: (S)</li>
<li><strong>event_id</strong> (I) Foreign key to the events table</li>
<li><strong>expression</strong>: (S)</li>
<li><strong>value</strong>: (I) </li>
</ul>
<h4>Conditions Table : UPDATE</h4>
<p><strong>Optional Parameters:</strong></p>
<li><strong>rule_id</strong>: (I) Foreign key to the rules table</li>
<li><strong>operation</strong>: (S)</li>
<li><strong>event_id</strong>: (I) Foreign key to the events table</li>
<li><strong>expression</strong>: (S)</li>
<li><strong>value</strong>: (I)</li>
<hr>

<h4>Events Table : INSERT</h4>
<p><strong>Required Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>description</strong>: (S)</li>
<li><strong>value</strong>: (I)</li>
<h4>Events Table : UPDATE</h4>
<p><strong>Optional Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>description</strong>: (S)</li>
<li><strong>value</strong>: (I)</li>
<hr>

<h4>Rewards Table : INSERT</h4>
<p><strong>Required Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>description</strong>: (S)</li>
<li><strong>image_url</strong>: (S)</li>
<h4>Rewards Table : UPDATE</h4>
<p><strong>Optional Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>description</strong>: (S)</li>
<li><strong>active</strong>: (B)</li>
<li><strong>image_url</strong>: (S)</li>
<hr>

<h4>Rules Table : INSERT</h4>
<p><strong>Required Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>rewards_id</strong>: (I) Foreign key to the rewards table</li>
<p><strong>Optional Parameters:</strong></p>
<li><strong>start_date</strong>: (DT) Defaults to 0000-00-00 00:00:00 </li>
<li><strong>end_date</strong>: (DT) Defaults to 0000-00-00 00:00:00 </li>
<h4>Rewards Table : UPDATE</h4>
<p><strong>Optional Parameters:</strong></p>
<li><strong>name</strong>: (S)</li>
<li><strong>rewards_id</strong>: (I) Foreign key to the rewards table</li>
<li><strong>active</strong> (B)</li>
<li><strong>start_date</strong>: (DT)</li>
<li><strong>end_date</strong>: (DT)</li>
<hr>

<h2>Error Codes</h2>
<p><strong>400: </strong> Missing field</p>
<p><strong>422: </strong> Field not found</p>
<p><strong>500: </strong> Results could not be retrieved</p>

<h2>Receivers</h2>
<p>The users that receive achievements via activities.</p>
<p>The main goal of receivers is to help you link your users (employee_id) with this application. When you register an activity you send a employee_id(if the id does not exist a new receiver will be created automatically) so you can easily match receivers to your users.</p>
<h3>Fields</h3>
<p><strong>employee_id:</strong> (S) EPA Employee ID</p>
<p><strong>points</strong>: (I) The total number of points this user has already achieved.</p>
<h2>Levels</h2>
<p>A level is effectively a rank that corresponds to the receiver and is earned through accumulating points during the completion of events.</p>
<h3>Fields</h3>
<p><strong>name</strong>: (S) The name of the level. For instance "Beginner".</p>
<p><strong>description</strong>: (T) A text explaining the level.</p>
<p><strong>value</strong>: (I) The point threshold for the level.</p>
<p><strong>image_url</strong>: (S) The url of an image that represents the level.</p>
<h2>Rewards</h2>
<p>Badges a receiver can win. Receivers gain rewards when the rule(s) to gain that reward is completed.</p>
<p>Rewards are prizes receivers can win. Rules determine if an activity has achieved the receiver a reward.</p>
<p>If there are many rules linked to the same reward, the&nbsp;<strong>first one to become valid</strong>&nbsp;will award the reward to the user. A specific user can only receive the same reward once.</p>
<p>To clarify concepts between rules and conditions:</p>
<ul>
<li>A user will receive a reward whenever&nbsp;<strong>any</strong>&nbsp;of the rules linked to that reward is completed.</li>
<li><strong>All</strong>&nbsp;conditions of a rule must be met in order for the rule to be completed.</li>
</ul>
<h3>Fields</h3>
<p><strong>name</strong>: (S) The name of the reward. For instance "Night owl" or "Level 35".</p>
<p><strong>description</strong>: (T) A text explaining the reward.</p>
<p><strong>active</strong>: (B) Is the reward active?</p>
<p><strong>image_url</strong>: (S) The url of an image that represents the reward.</p>
<h2>Events</h2>
<p>What a receiver needs to do in order to gain a reward, like clicking a button, downloading a file or watching a video.</p>
<p>Events are where you define what a user can do in order to gain rewards. For instance: "click a button", "read an article", "download a file"...</p>
<h3>Fields</h3>
<p><strong>name</strong>: (S) The name of the event. Like "Send a comment"</p>
<p><strong>description</strong>: (T) A detailed description of the event</p>
<p><strong>value</strong>: (I) The number of points that this event is worth. When a receiver performs this event will gain those points. It can be zero.</p>
<h2>Rules</h2>
<p>A rule is a collection of one or more conditions that a receiver needs to complete in order to gain the reward associated with the rule.</p>
<p>A rule links a reward with the conditions needed to achieve it. If there are no dates it will be available always if there is any date it will be available starting or until the dates indicate.</p>
<p>start_date: (DT) the date when this rule will come into effect. Can be blank.</p>
<p>end_date: (DT) The date when this rule will stop. Can be blank.</p>
<p>If only the start date is provided only activities with a date equal or greater than the start date will count.</p>
<p>If only the end date is provided only activities with a date equal or lower than the end date will count.</p>
<p>If both dates are provided only activities between those dates will be counted.</p>
<p>For a rule to be valid&nbsp;<strong>ALL conditions</strong>&nbsp;must be fulfilled.</p>
<h2>Conditions</h2>
<p>The logic that will determine if a rule has been fulfilled. All conditions need to be fulfilled for a rule to be completed.</p>
<p>Express the logic that a rule must fulfill to be considered as&nbsp;<em>done</em>.</p>
<p>operation: (S)&nbsp;<em>points</em>&nbsp;or&nbsp;<em>counter</em>&nbsp;Points will sum the value of the activity while Counter will count the number of activities.</p>
<p>expression: (S)&nbsp;<em>gte (>=)</em>&nbsp;or&nbsp;<em>gt (>)</em>&nbsp;or&nbsp;<em>eq (=)</em>&nbsp;or&nbsp;<em>lt (<)</em>&nbsp;or&nbsp;<em>lte (<=)</em>&nbsp;The logic to be performed for the operation</p>
<p>value: (I) The total of points or counts needed to complete the condition.</p>
<p>action: (S) The id of the action. Can be blank.</p>
<p>Let's see some examples:</p>
<p><code>{ operation: :points, expression: :gte, value: 1000 }</code>&nbsp;This condition will check all activities performed by a user, sum all their values and will return true if the result is 1,000 or more</p>
<p><code>{ operation: :points, expression: :gte, value: 1000, action: 'xxx' }</code>&nbsp;Same as before but only for activities with the ID&nbsp;<em>xxx</em></p>
<p><code>{ operation: :counter, expression: :gte, value: 63 }</code>&nbsp;This condition will count the number of activities performed and return true if result is 63 or more.</p>
<p><code>{ operation: :counter, expression: :gte, value: 63, action: 'xxx' }</code>&nbsp;Same as before but only for activities with the ID&nbsp;<em>xxx</em></p>
<p>For a rule to be valid&nbsp;<strong>ALL conditions</strong>&nbsp;must be true.</p>
<p>You can not query conditions directly but only as part of Rules.</p>
<h2>Achievements</h2>
<p>Achievements are rewards earned by a specific receiver.</p>
<p>They are created internally based on activities and rules.</p>
<h2>Activities</h2>
<p>An activity is an action that a receiver has performed. The things you want to gamify in your application, like: "Downloading a file". When the user performs that action you should generate an activity that needs to be submitted to this application.</p>
<p>Then a check will be done to see if the activity awarded the receiver any rewards.</p>
<p>If the id of the user does not exist (employee_id) it will be created automcatically.</p>
<p>Value is optional. If you provide a value it will override the one from the event.</p>
<h2>Database Architecture</h2>
<img src="gamification_database.jpg" alt="Database Diagram" />


</body>
</html>