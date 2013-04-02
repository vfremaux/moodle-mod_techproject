Version
#######

Moodle 2.3 beta

This "techproject" activity module is intended to give a complete project driving environement in a pedagogical environement, allowing to teach first principles of pragmatic project management to students through standard steps that are collecting needs, specifying solution, and controlling developement tasks. Its goal is to fit moodle with a fully featured project management tool that could elsewhere be used to manage real projects. Teacher/students interaction will be based on state-of-the art practices in Moodle and using most of the core tools and API we can.

Content:
########

Although this module contains all needed resources for it to work, some of its resources, in this first version, should be installed manually.

This module holds : 

- php files and additives required by any Moodle module.

- text and labels language resources. You'll find a language file and an help directory for each supported language.

- a set of dedicated icons in a "pix/p/" directory.


Installing:
###########

Once the archive is unzipped in the {$CFG->dirroot}/mod directory of your moodle implémentation,

1. Installing label and texts

copy the language resources (settled in the /lang directory of the module) in the appropriate directories of language resources of Moodle : {$CFG->dirroot}/lang, or "moodledata/lang" directory for automatically installed packages. From the 1.7+ version, you may also let the language files just where they are.

3. Moving pix folder

Copy the pix/p/ directory of the distribution into the genuine location for your pixes.

Activation & Settings: 
######################

To activate the module, you should now perform the "logical" installation. Connect to your Moodle as admin, and browse to the admin interface. 

The data model and parameters for the coursetracking module are automatically installed.

Browse to the module configuration screen: your may see a new "techproject" entry in the list of available modules. Enable it if necessary. 

Refer to the general help of the module to see how to use some of the provided options.

Trying the module:
##################

In a test course, add an instance of the techproject module, browse to the module and try it out.

Module instance parametrization:
################################

A new instance of a techproject module has some parameters to alter its behaviour. You may define start and end dates, let students edit one or more entities, manage how the module will compile and deliver grades, and so on.

Roadmap: 
########

Here are some enhancements envisaged for a near future. 

Step 1 : May 2007 - consolidation

Infrastructure
   Review and finishing out notifications
   Review and finishing out logging
Entity editing
   More constraints checkings on dates
   Better cleaning on assignation lists to avoid inconsistencies
Deliverables
   File attachements on deliverables .. DONE
Entity displaying
   More indicators and propagated displays .. DONE
Teacher tools
   loading a project wth a preset XML definition 
   exporting to a provisional XML simple schema .. DONE partially
   message board to send 'advices' and comments to group members

Step 2 : More tools and enhancements

Additional Tools
   Gantt and Perth diagrams ... GANTT DONE
   Todo list (small and immediate pragmatic tasks). Can serve as bugtracker. 
   CVS remote control for initiating resource repositories (scriptable)
