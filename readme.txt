MyBB Status Manager (in development): 

QUICK NOTE (IMPORTANT): THIS IS AN ALPHA RELEASE! This is NOT live forum safe at this time. Please give us a couple short weeks, we will have a forum-ready beta version coming out very shortly! 

In the meantime, we strongly recommend installing this on a development forum only. Any testing, suggestions, bug reports, and ideas are immensely helpful and welcome! Any and all contributions are welcome and greatly appreciated. 

Credits: 
@Omar G. For many suggestions and for very helpful input and help throughout this process. 
@Eldenroot for feedback, suggestions, and various feature requests. 
@Shade for helping with various development questions and sharing expertise
@Sawedoff for feedback, ideas, and testing. 
@Whiteneo for feedback, ideas, and testing. And for helping immensely with our my alerts integration. 
@tc4me for a great deal of testing and feedback
@MyBB Thank You/Like plugin: With permission, we've based our my alerts integration on the implementation with the TYL plugin. A huge thank you to the developers, who have made Statusfeed's myalerts integration possible. 

--------------------------------
ABOUT:
--------------------------------

Status Manager is a fully featured profile comments/status updates plugin made to meet the modern needs of MyBB forums in 2020. It has full support for replies, likes, portal/profile support, postbit popups, and more. Additionally, it is powered by Ajax. Posting, editing, or deleting comments and replies will not generate a page refresh! 

It is intended as a replacement for various features within MyProfile to create a new plugin that is modernized and properly compatible with newer MyBB releases. 

FEATURES: 

 - Full support for replies
 - Optional support for likes
 - Built in alerts (MyAlerts integration coming soon)
 - Customizable. Uses its own stylesheets and templates. 
 - No page refresh required to post, edit, like, or delete a status. 
 - Shows up on the portal, profile, index (optional), and postbox (optional)
 - Optional “show user’s status history” on the postbit. Opens a popup when clicked. 
 - Ability to link to any individual status. 
 - “Community Wall” - a page with a history of all community status updates.  

-----------------------------------------
KNOWN ISSUES: 
-----------------------------------------

Status Manager is pre beta! Known issues will be tracked extensively on GitHub. 

 - Edit form does not work. Redirects and does not edit. 
 - Delete form does not remove status via javascript, but does delete. 
 - Native alerts sometimes act funny if another user comments on a status you've commented on. 
 - Tooltip on "show likes" sometimes acts odd. 
 - Comments display is broken on the portal. 
 - No alerts generated on "like"
 - Pagination implementation is clunky on profile. 
 - Postbit popup does not display properly on default theme (displays in footer). 

-----------------------------------------
FAQ: 
-----------------------------------------

- Why is there a maximum comment limit? 

	At this time, pagination does not exist within comments. To prevent an epilogue from taking up your entire screen on an endless scroll, the plugin has a max-comments feature. We will be addressing this very shortly (hopefully before release, or in a version soon after). 

- Should I use native alerts? 

	We recommend MyAlerts if you have it installed. Otherwise, definitely leave native alerts enabled. Otherwise, users won't know if someone has commented on their wall! 



-----------------------------------------
CONTRIBUTING: 
-----------------------------------------

Many have already contributed privately. These contributions are immensely helpful! I cannot possibly overstate just how much I greatly appreciate the help that I've received on this plugin. It has made this development possible. 

If you'd like to help, please fork on Github and create any pull requests for contributions! I will give a shout out to all contributors on the official release, and look forward to collaborating on the development of this plugin. 

Some guidelines for contributions: 

 - I've brought this project back from the dead (from all the way back in 2014). There are some issues that are still being fixed regarding the code itself. As such, there are bits of inline CSS and other issues that are being resolved. If you catch something and would like to fix it, please do not hesitate. It is a huge help for the final project. 

 - The initial release will focus on stability, not necessarily on additional features. That being said, nothing is off the table and all ideas and requests are welcome. A follow up release will add additional features! 

 - I need help with the Javascript aspect of things. It's not my strong suit. I've gotten almost all of the Ajax to work as intended, but there are still bits and pieces of inline JS still hanging in the templates. If you see something that can be improved, please do not hesitate. It will be a huge help for the final project! 

-----------------------------------------
INSTALLATION: 
-----------------------------------------

Upload the contents of the Upload directory to your forum, overwriting any files if prompted. 

-----------------------------------------
LICENSE: 
-----------------------------------------

   This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

