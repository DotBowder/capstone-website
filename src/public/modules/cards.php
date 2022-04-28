<?php
use Util\Security;

include_once PUBLIC_FILES . '/modules/button.php';

// Keeps track of how many cards have been generated. This is available globally.
$numCardsCreated = 0;


/**
 * Renders the HTML for the card group that displays projects.
 * 
 * The projects passed to this render function will ALL be rendered. All projects that should not be rendered (e.g.
 * projects that are not published or should be hidden) should be filtered out before calling this function.
 *
 * @param \Model\CapstoneProject[] $projects the projects to display
 * @param boolean $browsing indicates whether the rendered cards are for browsing or viewing/editing user projects
 * @param boolean $showActions determines whether to show the actionable (Edit, Delete) buttons
 * @return void
 */
function renderProjectCardGroup($projects, $keywordsDao, $categoriesDao, $browsing = false) {
	global $numCardsCreated;
	global $image_dir;
	
	if(!$projects || count($projects) == 0) {
		return;
	}

    foreach ($projects as $p) {
        // Capture and format all of the variables we need before rendering the HTML
        $id = $p->getId();
        $title = Security::HtmlEntitiesEncode($p->getTitle());
        if (strlen($title) > 60) {
            // Restrict the title length
            $title = substr($title, 0, 60) . '...';
        }
        $description = Security::HtmlEntitiesEncode($p->getDescription());
        if (strlen($description) > 90) {
            // Restrict the description length
            $description = substr($description,0,90) . '...';
        }
        $status = $p->getStatus()->getName();
		$nda = $p->getNdaIp()->getName();
		$name = Security::HtmlEntitiesEncode($p->getProposer()->getFirstName()) 
		. ' ' 
		. Security::HtmlEntitiesEncode($p->getProposer()->getLastName());
		
		$courses = '';
		$categories = $categoriesDao->getCategoriesForEntity($id);
	
		foreach ($categories as $c){
			if ($courses == '') {
				$courses .= '' . $c->getName() . '';
			}
			else {
				$courses .= ', ' . $c->getName() . '';
			}
		}

        // The details string contains the small text for the project
		$details = $p->getType()->getName() . ' ' . $p->getDateStart()->format('Y') . '<br/>';
		$details .= "Courses: $courses <br/>";
		$details .= "Proposer: $name <br/>";
        if (!$browsing) {
            $details .= "Status: $status";
        }

		$extra = '';
		$preexistingKeywords = $keywordsDao->getKeywordsForEntity($id);
		if($preexistingKeywords){
			foreach ($preexistingKeywords as $k) {
				if (trim(Security::HtmlEntitiesEncode($k->getName())) != '') {
					$extra .= '<span class="badge badge-light keywordBadge">' . Security::HtmlEntitiesEncode($k->getName()) . '</span>';
				}
			}
		}
		

		$image = false;
		$images = $p->getImages();
		if($images) {
			foreach($images as $i) {
				if($i->getIsDefault()){
					$image = $i->getId();
					break;
				}
			}
		}
		
        if (!$image) {
            $image = $image_dir . 'assets/img/capstone_test.jpg';
		}
		else {
            $image = $image_dir . "images/$image";
		}

		if(!@getimagesize($image)){
			$image = $image_dir . 'assets/img/capstone_test.jpg';
		}
		
		
	

        $dateUpdated = $p->getDateUpdated()->format('Y-m-d');
		$lastUpdated = "<br/>Last Updated: $dateUpdated";
		
		$published = !$p->getIsHidden();
		
		renderProjectCard($id, $title, $description, $details, $image, $status, $lastUpdated, 
			$numCardsCreated, $browsing, $published, $extra, $nda);

        $numCardsCreated++;
    }
}


function renderAdminProjectCardGroup($projects, $keywordsDao, $categoriesDao, $types, $statuses, $browsing = false) {
	global $numCardsCreated;
	global $image_dir;
	
	if(!$projects || count($projects) == 0) {
		return;
	}

    foreach ($projects as $p) {	
		renderAdminProjectCard($p, $categoriesDao, $numCardsCreated, $types, $browsing);
        $numCardsCreated++;
    }
}

/***************************************************************/
/* Render card group for all preferred course listings */
/***************************************************************/

function renderCourseListingCardGroup($preferredCourses) {
	global $numCardsCreated;
	
	if(!$preferredCourses || count($preferredCourses) == 0) {
		return;
	}

    foreach ($preferredCourses as $p) {	
		renderCourseListingCard($p);
        $numCardsCreated++;
    }
}

/***************************************************************/
/* Render individual cards for preferred course listings */
/***************************************************************/

function renderCourseListingCard($preferredCourse) {
	
	// Capture and format all of the variables we need before rendering the HTML
	$id = $preferredCourse->getId();
	$code = Security::HtmlEntitiesEncode($preferredCourse->getCode());
	$name = Security::HtmlEntitiesEncode($preferredCourse->getName());

	if (strlen($name) > 60) { // Restrict the title length
		$name = substr($name, 0, 60) . '...';
	}
    
	// $editButton = createLinkButton("pages/editProject.php?id=$id", 'Edit') : '';
	// $deleteButton = (!$browsing) ? createProjectDeleteButton($id, $num) : '';
		
	
	echo "
	<tr id='preferredCourse$id' style='border-bottom: 1px solid black;'>
		<td class='col-sm-3' id='preferredCourseBody$id' >
			<textarea class='col-sm-12' id='courseCode$id' name='courseCode'>$code</textarea>
		</td>
		<td class='col-sm-3'>
			<textarea class='col-sm-12' id='courseName$id' name='courseName'>$name</textarea>
		</td>
		<td class='col-sm-2'>
			<button class='btn btn-outline-danger deleteCourseBtn' id='deleteCourseBtn$id' type='button'>
				Delete
			</button>
			<button class='btn btn-outline-primary capstone-nav-btn editCourseBtn' id='editCourseBtn$id' type='button'>
				Save Changes
			</button>

		</td>
	</tr>

	<script type='text/javascript'>
		
		$(document).ready(function() {
			$('#preferredCourse$id').hover(function() {
				$(this).css('background-color', '#f8f9fa');
			}, function() {
				$(this).css('background-color', 'white');
			});
		});

		$('#deleteCourseBtn$id').on('click', function() {
			let res = confirm('You are about to delete a course. This action cannot be undone.');
			if(!res) return false;

			let courseId = '$id';
			let data = {
				action: 'deleteCourse',
				id: courseId,
			};
			api.post('/courses.php', data).then(res => {
				$('#preferredCourse$id').hide();
				snackbar(res.message, 'success');
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});

		$('#editCourseBtn$id').on('click', function () {
			// Capture the data we need
			let courseName = document.getElementById('courseName$id').value;
			let courseCode = document.getElementById('courseCode$id').value;
			courseCode = courseCode.replace(/\s/g, '');
			let courseId = '$id';

			let data = {
				action: 'editCourse',
				id: courseId,
				name: courseName,
				code: courseCode
			};

			// Send our request to the API endpoint
			api.post('/courses.php', data).then(res => {
				window.location.reload();
				snackbar(res.message, 'success');
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}
/***************************************************************/
/***************************************************************/

/**
 * Renders the HTML required for a project card.
 *
 * @param string $id the ID of the project
 * @param string $title the title of the project
 * @param string $description the description of the project
 * @param string $details the details for the project, including type and year
 * @param string $imageLink the link to the image to use for the project
 * @param string $status the current status of the project
 * @param string $lastUpdated the date the project was last updated
 * @param integer $num the index of this card globally (which number card is it)
 * @param boolean $browsing whether to hide or show the edit and delete buttons. A value of true hides.
 * @return void
 */
function renderProjectCard($id, $title, $description, $details, $imageLink, $status, $lastUpdated, $num, $browsing, $published, $extra, $nda) {
    $statusColor = ($status == 'Awaiting Approval' || $status == 'Rejected') ? 'red' : 'inherit';
    $viewButton = $published ? createLinkButton("pages/viewSingleProject.php?id=$id", 'View') : '';
	$editButton = !$browsing ? createLinkButton("pages/editProject.php?id=$id", 'Edit') : '';
	$deleteButton = !$browsing ? createProjectDeleteButton($id, $num) : '';
	$classes = '';
	if ($nda != 'No Agreement Required')
		$classes .= 'reqNDA ';

	// decode rich html saved from rich text
	$descriptionDecoded = htmlspecialchars_decode($description);
	
    echo "
	<div class='masonry-brick $classes' id='projectCard$num'>
		<a href='pages/viewSingleProject.php?id=$id' target='_blank' style='color: black'>
			<img class='card-img-top' id='projectImg$id' src='$imageLink' alt='Card Image Capstone' />
		</a>
		<div class='card-body' id='projectCardBody$num'>
			<h6>$title</h6>
			$descriptionDecoded
			<br>
			<small class='text-muted'>$details</small>
			<div style='position: absolute; float: left; margin-right: 10px; bottom: 10px;'>";
				//<h6><p style='color: $statusColor'>$status</p></h6>
echo "			<small class='text-muted'>$extra</small><br>
				$viewButton
				$editButton
				$deleteButton
				<small id='small$id' class='text-muted lastUpdatedSmall'>$lastUpdated</small>
			</div>
		</div>
		<br/>
	</div>

	<script type='text/javascript'>
		$(document).ready(function() {
			$('#projectCard$id').hover(function() {
				$(this).css('background-color', '#f8f9fa');
				$('#projectImg$id').css('transition', 'all .2s ease-in-out');
			}, function() {
				$(this).css('background-color', 'white');
			});
		});
	</script>
	";
}


function renderAdminProjectCard($project, $categoriesDao, $num, $types, $browsing) {
	global $image_dir;
	
	// Capture and format all of the variables we need before rendering the HTML
	$id = $project->getId();
	$title = Security::HtmlEntitiesEncode($project->getTitle());
	$description = Security::HtmlEntitiesEncode($project->getDescription());
	$nda = $project->getNdaIp()->getName();
	$archived = $project->getIsArchived();
	$partnername = Security::HtmlEntitiesEncode($project->getProposer()->getFirstName()) . " " . Security::HtmlEntitiesEncode($project->getProposer()->getLastName());
	$email = $project->getProposer()->getEmail();
	$proposerPhone = $project->getProposer()->getPhone();
	$published = !$project->getIsHidden();
	$dateUpdated = $project->getDateUpdated()->format('Y-m-d');
	$status = $project->getStatus()->getName();
	
	if (strlen($title) > 60) { // Restrict the title length
		$title = substr($title, 0, 60) . '...';
	}

	if (strlen($description) > 220) { // Restrict the description length
		$description = substr($description,0,220) . '...';
	}

	

	$extra = '';
	// Set Extra Information for Admin Browse (If Archived, show that, if just a created project show nothing)
	if ($archived) {
		$extra = "Archived";
	}


	//Make a Category checkbox form based on type
	$courses = '<p>';
	$categories = $categoriesDao->getCategoriesForEntity($id);

	foreach ($categories as $c){
		if ($courses == '<p>') {
			$courses .= '' . $c->getName() . '';
		}
		else {
			$courses .= ', ' . $c->getName() . '';
		}
	}
	$courses .= '</p>';

	//Make a Type drop down based on type
	// $type_select = "<select id='typeselect$id' onchange='typeChange(\"$id\");'>";
	// foreach ($types AS $type){
	// 	$type_select .= "<option value='".$type->getId()."' ".($type->getId() == $project->getType()->getId() ? 'selected':'').">".$type->getName()."</option>";
	// }
	// $type_select .= "</select>";
	
	$details = '';
	if (($project->getIsSponsored()))
			$details .= "<BR>Sponsored";
	// $details .= '<br/>Type: '. $type_select;
	$details .= '<br/>Categories: '. $courses;


	$image = false;
	$images = $project->getImages();
	if($images) {
		foreach($images as $i) {
			if($i->getIsDefault()){
				$image = $i->getId();
				break;
			}
		}
	}
	
	if (!$image) {
		$image = $image_dir . 'assets/img/capstone_test.jpg';
	} else {
		$image = $image_dir . "images/$image";
	}
	
	if(!@getimagesize($image)){
		$image = $image_dir . 'assets/img/capstone_test.jpg';
	}

	$lastUpdated = "Last Updated: $dateUpdated";
	
	$statusColor = '';
	$bannerColor = '';
	if ($archived) {
		$statusColor = '#434343';
		$bannerColor = '#bdbdbd';
	}
	elseif ($status == 'Created') {
		$statusColor = '#1c4587';
		$bannerColor = '#9fc5e8';
	}
	elseif ($status == 'Accepting Applicants') {
		if ($published) {
			$statusColor = '#274e13';
			$bannerColor = '#b6d7a8';
		}
		else {
			$statusColor = '#7f6000';
			$bannerColor = '#ffe599';
			$status = 'Approved but Unpublished';
		}
	}
	elseif ($status == 'Pending Approval') {
		$statusColor = '#783f04';
		$bannerColor = '#f9cb9c';
	}
	elseif ($status == 'Incomplete') {
		$statusColor = '#7f6000';
		$bannerColor = '#ffe599';
	}
	elseif ($status == 'Rejected') {
		$statusColor = '#660000';
		$bannerColor = '#ff8a6d';
	}
	else {
		$statusColor = '#434343';
		$bannerColor = '#bdbdbd';
	}
	
	$statusColorExtra = ($extra == 'Category Placement' || $status == 'Rejected') ? '#fc4a3a' : (($extra == 'Archived') ? '#ffcc00' : 'inherit');
    
	$viewButton = $published ? createLinkButton("pages/viewSingleProject.php?id=$id", 'View') : '';
	$editButton = (!$browsing && !$archived) ? createLinkButton("pages/editProject.php?id=$id", 'Edit') : '';
	$deleteButton = (!$browsing) ? createProjectDeleteButton($id, $num) : '';
	$publishButton = '';
	if (!$browsing && $published) 
		$publishButton = createProjectUnpublishButton($id, $num);
	if (!$browsing && !$published) 
		$publishButton = createProjectPublishButton($id, $num);
	
	$unarchiveButton = '';
	if (!$browsing && $archived) 
		$unarchiveButton = createProjectUnarchiveButton($id, $num);
	if (!$browsing && !$archived) 
		$unarchiveButton = createProjectArchiveButton($id, $num);
	
	// classes for sorting
	$classes = '';
	
	if ($status == 'Accepting Applicants') {
		$classes .= 'acceptingApplicants';
	}
	elseif ($status == 'Approved but Unpublished') {
		$classes .= 'approvedUnpublished';
	}
	elseif ($status == 'Pending Approval') {
		$classes .= 'pendingApproval';
	}
	elseif ($status == 'Rejected') {
		$classes .= 'rejected';
	}
	elseif ($status == 'Created') {
		$classes .= 'created';
	}
	
	// decode rich html saved from rich text
	$descriptionDecoded = htmlspecialchars_decode($description);
	
	echo "
	<tr id='projectCard$id' style='border-bottom: 1px solid black;' class='$classes'>
	<td class='col-sm-1' id='projectBanner' style='background-color: $bannerColor;'>
		<span style='color: $statusColor'>$status</span><BR>
	</td>
	<td>

	";
	if (!$archived){
		echo "
		<a href='pages/viewSingleProject.php?id=$id' target='_blank' style='color: black'>
			<img class='card-img-admin' id='projectImg$id' src='$image' alt='Card Image Capstone' />
		</a>
		";
	}
	else {
		echo "
		<img class='card-img-admin' id='projectImg$id' src='$image' alt='Card Image Capstone' />
		";
	}
	echo "
		</td>
		<td class='col-sm-3' id='projectCardBody$id' >
			<h6>$title</h6>
			<small class='text-muted'>$descriptionDecoded</small>
		</td>
		<td class='col-sm-3'>
			<small class='text-muted'>NDA: $nda $details</small>
			</td>
		<td class='col-sm-2'>
			
			<small class='text-muted'>Proposer: <a href='mailto:$email'>$partnername</a><BR>Phone: $proposerPhone<BR>Email: $email</small><BR>
			
			</td>
		<td class='col-sm-2'>
			<small id='small$id' class='text-muted lastUpdatedSmall'>$lastUpdated</small><BR>
			$viewButton
			$editButton
			$deleteButton
			$unarchiveButton
			$publishButton
		</td>
	</tr>

	<script type='text/javascript'>
		$(document).ready(function() {
			$('#projectCard$id').hover(function() {
				$(this).css('background-color', '#f8f9fa');
				$('#projectImg$id').css('transition', 'all .2s ease-in-out');
			}, function() {
				$(this).css('background-color', 'white');
			});
		});
	</script>
	";
}


/**
 * Creates the HTML and associated JavaScript required for the project 'Delete' button functionality.
 *
 * @param string $title the title of the project
 * @param string $projectId
 * @param integer $cardNumber
 * @return void
 */
function createProjectDeleteButton($projectId, $cardNumber) {
	return "
	<button class='btn btn-outline-danger deleteProjectBtn' id='deleteProjectBtn$projectId' type='button'>
		Delete
	</button>
	
	<script type='text/javascript'>
		$('#deleteProjectBtn$projectId').on('click', function() {
			let res = confirm('You are about to delete a project. This action cannot be undone.');
			if(!res) return false;
			let projectId = '$projectId';
			let data = {
				action: 'deleteProject',
				id: projectId,
			};
			api.post('/projects.php', data).then(res => {
				$('#projectCard$projectId').hide();
				snackbar(res.message, 'success');
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}

/**
 * Creates the HTML and associated JavaScript required for the project 'Unarchive' button functionality.
 *
 * @param string $title the title of the project
 * @param string $projectId
 * @param integer $cardNumber
 * @return void
 */
function createProjectUnarchiveButton($projectId, $cardNumber) {
	return "
	<button class='btn btn-outline-warning' id='unarchiveProjectBtn$projectId' type='button'>
		Unarchive
	</button>
	
	<script type='text/javascript'>
		$('#unarchiveProjectBtn$projectId').on('click', function() {
			let res = confirm('You are about to unarchive a project.');
			if(!res) return false;
			let projectId = '$projectId';
			let data = {
				action: 'unarchiveProject',
				id: projectId,
			};
			api.post('/projects.php', data).then(res => {
				$('#projectCard$projectId').hide();
				snackbar(res.message, 'success');
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}

/**
 * Creates the HTML and associated JavaScript required for the project 'Archive' button functionality.
 *
 * @param string $title the title of the project
 * @param string $projectId
 * @param integer $cardNumber
 * @return void
 */
function createProjectArchiveButton($projectId, $cardNumber) {
	return "
	<button class='btn btn-outline-warning' id='archiveProjectBtn$projectId' type='button'>
		Archive
	</button>
	
	<script type='text/javascript'>
		$('#archiveProjectBtn$projectId').on('click', function() {
			let res = confirm('You are about to Archive a project.');
			if(!res) return false;
			let projectId = '$projectId';
			let data = {
				action: 'archiveProject',
				id: projectId,
			};
			api.post('/projects.php', data).then(res => {
				snackbar(res.message, 'success');
				$('#projectCard$projectId').hide();
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}

/**
 * Creates the HTML and associated JavaScript required for the project 'Archive' button functionality.
 *
 * @param string $title the title of the project
 * @param string $projectId
 * @param integer $cardNumber
 * @return void
 */
function createProjectUnpublishButton($projectId, $cardNumber) {
	return "
	<button class='btn btn-outline-warning' id='unpublishProjectBtn$projectId' type='button'>
		Unpublish 
	</button>
	
	<script type='text/javascript'>
		$('#unpublishProjectBtn$projectId').on('click', function() {
			let projectId = '$projectId';
			let data = {
				action: 'unpublishProject',
				id: projectId,
			};
			api.post('/projects.php', data).then(res => {
				snackbar(res.message, 'success');
				$('#projectCard$projectId').css({ opacity : '0.5', filter:  'alpha(opacity=50)'  });
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}

/**
 * Creates the HTML and associated JavaScript required for the project 'Archive' button functionality.
 *
 * @param string $title the title of the project
 * @param string $projectId
 * @param integer $cardNumber
 * @return void
 */
function createProjectPublishButton($projectId, $cardNumber) {
	return "
	<button class='btn btn-outline-warning' id='publishProjectBtn$projectId' type='button'>
		Publish
	</button>
	
	<script type='text/javascript'>
		$('#publishProjectBtn$projectId').on('click', function() {
			let projectId = '$projectId';
			let data = {
				action: 'publishProject',
				id: projectId,
			};
			api.post('/projects.php', data).then(res => {
				snackbar(res.message, 'success');
				$('#projectCard$projectId').css({ opacity : '0.5', filter:  'alpha(opacity=50)'  });
			}).catch(err => {
				snackbar(err.message, 'error');
			});
		});
	</script>
	";
}


