// Selectors used by this script
let typeSelector = 'data-type';
let formIdTmpl   = 'migrationForm';
let button       = 'migrationBtn';
let tryLimit     = 3;

/**
 * Storage for migrateables
 * @var {Object}  migrateablesList
 */
var migrateablesList = {};

/**
 * Counter of how many times the same migration was tried to perfrom
 * @var {Integer}  tryCounter
 */
var tryCounter = 0;

/**
 * State. As long as this state is set to true, the migration will be
 * continued automatically regarding the pernding queue in the migrateablesList.
 * @var {Boolean}  contiue
 */
var continueState = true;

/**
 * Submit the migration task by pressing the button
 * 
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let submitTask = function(event, element) {
  event.preventDefault();

  let type   = element.getAttribute(typeSelector);
  let formId = formIdTmpl + '-' + type;
  let task   = element.parentNode.querySelector('[name="task"]').value;

  tryCounter++;

  ajax(formId, task)
    .then(res => {
      // Handle the successful result here
      responseHandler(type, res);

      if(tryCounter >= tryLimit) {
        // We reached the limit of tries --> looks like we have a network problem
        updateMigrateables(type, {'success': false, 'message': Joomla.JText._('COM_JOOMGALLERY_ERROR_NETWORK_PROBLEM'), 'data': false});
      } else if(continueState) {
        // Kick off the next task
        submitTask(event, element);
      }
    })
    .catch(error => {
      // Handle any errors here
      console.error(error);
      addLog(error, type, 'error');
    });
};

/**
 * Perform an ajax request in json format
 * 
 * @param   {String}   formId   Id of the form element
 * @param   {String}   task     Name of the task
 * 
 * @returns {Object}   Result object
 *          {success: true, status: 200, message: '', messages: {}, data: { { {success, data, continue, error, debug, warning} }}
 */
let ajax = async function(formId, task) {

  // Catch form and data
  let formData = new FormData(document.getElementById(formId));
  formData.append('format', 'json');

  if(task == 'migration.start') {
    formData.append('id', getNextMigrationID(formId));
  }

  // Set request parameters
  let parameters = {
    method: 'POST',
    mode: 'same-origin',
    cache: 'default',
    redirect: 'follow',
    referrerPolicy: 'no-referrer-when-downgrade',
    body: formData,
  };

  // Set the url
  let url = document.getElementById(formId).getAttribute('action');

  // Perform the fetch request
  let response = await fetch(url, parameters);

  // Resolve promise as text string
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    // Catch network error
    return {success: false, status: response.status, message: response.message, messages: {}, data: {error: txt, data:null}};
  }

  if(txt.startsWith('{"success"')) {
    // Response is of type json --> everything fine
    res = JSON.parse(txt);
    res.status = response.status;
    res.data   = JSON.parse(res.data);
  } else if (txt.includes('Fatal error')) {
    // PHP fatal error occurred
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    // Response is not of type json --> probably some php warnings/notices
    let split = txt.split('\n{"');
    let temp  = JSON.parse('{"'+split[1]);
    let data  = JSON.parse(temp.data);
    res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
  }

  // Make sure res.data.data.queue is of type array
  if(typeof res.data.data != "undefined" && res.data.data != null && 'queue' in res.data.data) {
    if(res.data.data.queue.constructor !== Array) {
      res.data.data.queue = Object.values(res.data.data.queue);
    }
  }

  return res;
}

/**
 * Perform a migration task
 * @param   {String}   formId   Id of the form element
 * 
 * @returns {String}   Id of the database record to be migrated
 */
let getNextMigrationID = function(formId) {
  let type  = formId.replace(formIdTmpl + '-', '');
  let form  = document.getElementById(formId);

  let migrateable = atob(form.querySelector('[name="migrateable"]').value);
  migrateable = JSON.parse(migrateable);

  // Overwrite migrateable in list
  migrateablesList[type] = migrateable;

  // Loop through queue
  for (let id of migrateable.queue) {
    if (!(id in migrateable.successful) && !(id in migrateable.failed)) {
      migrateablesList[type]['currentID'] = id;
      break;
    }
  }

  return migrateablesList[type]['currentID'];
}

/**
 * Handle migration response
 * 
 * @param   {Object}   response   The response object in the form of
 *          {success: true, status: 200, message: '', messages: {}, data: { {success, data, continue, error, debug, warning} }}
 * 
 * @returns void
 */
let responseHandler = function(type, response) {
  if(response.success == false)  {
    // Ajax request failed
    addLog(response.message, type, 'error');
    addLog(response.messages, type, 'error');

    // Try again...
  }
  else  {
    // Ajax request successful
    if(!response.data.success)
    {
      // Migration failed
      addLog('[Migrator.js] Migration of '+type+' with id = '+migrateablesList[type]['currentID']+' failed.', type, 'error');
      logMessages(type, response.data);

      // Stop autimatic continuation if requested from backend
      if(!response.data.continue || response.data.continue == null || response.data.continue == false) {
        continueState = false;
      }

      // Update migrateables
      updateMigrateables(type, response.data);
    }
    else
    {
      // Save record successful
      logMessages(type, response.data);
      addLog('[Migrator.js] Migration of '+type+' with id = '+migrateablesList[type]['currentID']+'  successful.', type, 'success');

      // Stop autimatic continuation if requested from backend
      if(!response.data.continue || response.data.continue == null || response.data.continue == false) {
        continueState = false;
      }

      // Update migrateables
      updateMigrateables(type, response.data);

      // Reset tryCounter
      tryCounter = 0;
    }
  }
}

/**
 * Add a message to the logging output and the console
 * 
 * @param   {Mixed}    msg        One or multiple messages to be added to the log
 * @param   {String}   type       The type defining the logging output to use
 * @param   {String}   msgType    The type of message (available: error, warning, success, info)
 * @param   {Boolean}  console    True to add the message also to the console
 * @param   {Boolean}  newLine    True to add the message on a new line
 * @param   {Integer}  marginTop  Number of how much margin you want on the top of the message
 * 
 * @returns void
 */
let addLog = function(msg, type, msgType, console=false, newLine=true, marginTop=0) {
  if(!Boolean(msg) || msg == null || msg == '') {
    // Message is empty. Do nothing
    return;
  } else if(typeof msg === 'string') {
    // Your message is a simple string
    let tmp_msg = '';

    // Test if your string a json string
    try {
      tmp_msg = JSON.parse(msg);
    } catch (e) {
    }

    // Convert string to array
    if(tmp_msg !== '') {
      msg = Object.values(tmp_msg);
    } else {
      msg = [msg];
    }
  } else if(typeof msg === 'object') {
    // Your message is an object. Convert to array
    msg = Object.values(msg);
  }

  // Get logging output element
  let logOutput = document.getElementById('logOutput-'+type);

  // Loop through all messages
  msg.forEach((message, i) => {

    // Print in console
    if(console) {
      console.log(message);
    }

    // Create element
    let line = null;
    if(newLine) {
      line = document.createElement('p');
    } else {
      line = document.createElement('span');
    }

    // Top margin to element
    marginTop = parseInt(marginTop);
    if(marginTop > 0) {
      line.classList.add('mt-'+String(marginTop));
    }

    // Add text color
    line.classList.add('color-'+msgType);
    
    // Add message to element
    let msgType_txt = msgType.toLocaleUpperCase();
    line.textContent = '['+Joomla.JText._(msgType_txt)+']  '+String(message);

    // Print into logging output
    logOutput.appendChild(line);
  });
}

/**
 * Clear the logging output
 *
 * @param  {String}   type    The type defining the logging output to clear
 * 
 * @returns void
 */
let clearLog = function(type) {
  // Get logging output element
  let logOutput = document.getElementById('logOutput-'+type);

  // clear
  logOutput.innerHTML = '';
}

/**
 * Output all available messages from the result object
 *
 * @param  {String}   type   The type defining the content type to be updated
 * @param  {Object}   res    The result object in the form of
 *           {success: bool, data: mixed, continue: bool, error: string|array, debug: string|array, warning: string|array}
 * 
 * @returns void
 */
let logMessages = function(type, res) {
  // Available message types: error, debug, warning
  let available = ['error', 'debug', 'warning'];
  let msgTypes = {'error': 'error', 'debug': 'info', 'warning': 'warning'};

  available.forEach((value, index) => {
    if(!res[value] || !Boolean(res.data) || res.data == null) {
      return;
    }

    addLog(res[value], type, msgTypes[value]);
  });
}

/**
 * Update migrateable input field, progress bar and badges
 *
 * @param  {String}   type   The type defining the content type to be updated
 * @param  {Object}   res    The result object in the form of
 *           {success: bool, data: mixed, continue: bool, error: string|array, debug: string|array, warning: string|array}
 * 
 * @returns void
 */
let updateMigrateables = function(type, res) {
  let formId = formIdTmpl + '-' + type;
  let form   = document.getElementById(formId);

  if(!res.success && (!Boolean(res.data) || res.data == null || res.data == '')) {
    // Migration failed, but no data available in result

    // Create result data based on input field
    let migrateable = atob(form.querySelector('[name="migrateable"]').value);
    res.data = JSON.parse(migrateable);

    // See: Joomgallery\Component\Joomgallery\Administrator\Model\MigrationModel::migrate
    // Remove migrated primary key from queue
    res.data.queue = res.data.queue.filter(function(e) { return e !== migrateablesList[type]['currentID'] })

    // Add migrated primary key to failed object
    res.data.failed[migrateablesList[type]['currentID']] = res.message;
  }

  if(!Boolean(res.data.progress) || res.data.progress == null || res.data.progress == '') {
    // Update progress if not delivered with result object
    let total    = res.data.queue.lenght + Object.keys(res.data.successful).length + Object.keys(res.data.failed).length;
    let finished = Object.keys(res.data.successful).length + Object.keys(res.data.failed).length;
    res.data.progress = Math.round((100 / total) * (finished));
  }

  // Get badges
  let queueBadge = document.getElementById('badgeQueue-'+type);
  let resBadge = document.getElementById('badgeSuccessful-'+type);
  if(!res.success) {
    resBadge = document.getElementById('badgeFailed-'+type);
  }

  // Update migrateable input field
  let field = form.querySelector('[name="migrateable"]');
  field.value = btoa(JSON.stringify(res.data));

  // Update badges
  queueBadge.innerHTML = parseInt(queueBadge.innerHTML) - 1;
  resBadge.innerHTML   = parseInt(resBadge.innerHTML) + 1;

  // Update progress bar
  let bar = document.getElementById('progress-'+type);
  bar.setAttribute('aria-valuenow', res.data.progress);
  bar.style.width = res.data.progress + '%';
  bar.innerText = res.data.progress + '%';
}