import structureTpl from './views/structure.hbs';
import jstree from 'jstree';
import cssjstree from 'jstree/dist/themes/default/style.css';
import featherlight from 'featherlight';
import cssfeatherlight from 'featherlight/src/featherlight.css';

let requests = [];
requests.push($.ajax({
  url: '/admin/system/megastructure/',
  type: 'get'
}));
let request = $.when.apply($, requests);
let folders = {};
let structure = $('#structure');

request.then(function(data) {
  // для рекусивного вывода дерева, приходящая в шаблон переменная
  // и св-ва объекта должны именоваться одинаково
  folders.folders = data.structure;
  console.log('-----------------Дерево:', folders.folders);
  structure
    .append(structureTpl(folders))
    .jstree({
      'core': {
        'check_callback' : function (op, node, par, pos, more) {
          // запрет на перемещение ноды в root
          if (more && more.dnd && (op === 'move_node' || op === 'copy_node') && par.parent === null) {
            return false;
          }
          // запрет на перемещение в модуль
          if (par.li_attr['data-node-type'] === 'module' ) {
            return false;
          }
          return true;
        }
      },
      'plugins': ['dnd'],
      // 'plugins': ['dnd', 'checkbox',],
      // 'checkbox': {
      //   'visible': false
      // },
      // "themes" : { "stripes" : true }
    });
});

// перемещение папки
structure.on('move_node.jstree', function (e, data) {
  const nodeId = data.node.li_attr['data-node-id'];
  const action = data.node.li_attr['data-node-type'] === 'folder' ? 'move_folder' : 'move_node';
  const type = data.node.li_attr['data-node-type'] === 'folder' ? 'Раздел' : 'Модуль';
  const name = '\"' + data.node.text.trim() + '\"';
  let diff = {
    action: action,
    destination_folder_id: document.getElementById(data.parent).dataset.nodeId,
    position: data.position
  };
  data.node.li_attr['data-node-type'] === 'folder' ?
    diff['folder_id'] = nodeId :
    diff['node_id'] = nodeId;

  console.log('-----Нода переместилась:', data);

  $('.loader').toggleClass('loader-active');

  $.ajax({
    url: '/admin/system/megastructure/',
    type: 'post',
    data: diff,
    success: function(data) {
      setTimeout(function() {
        $('.loader').toggleClass('loader-active');
        //библиотека подключена глобально
        new PNotify({
          // title: type + ' перемещён!',
          text: type + ' ' + name + ' перемещён',
          type: 'success'
        });
      }, 500);
      console.log('------------------Успех:', data);
      // console.log(structure.jstree('get_json'));
    },
    error: function(data) {
      alert('Произошла ошибка, подробности в окне');
      let domHolder = document.getElementById('structure');
      let popup = document.createElement('div');
      popup.id = 'lightbox';
      domHolder.appendChild(popup);
      $.featherlight(data.responseText);
      $('.loader').toggleClass('loader-active');
      //библиотека подключена глобально
      new PNotify({
        // title: 'Произошла ошибка!',
        text: type + ' не был перемещён',
        type: 'error'
      });
      console.log('-----------------Ошибка:', data);
    }
  });
});

$('#create-folder').click(function(e) {
  e.preventDefault();
  $.ajax({
    url: '/admin/system/structure/folder/create/',
    // type: 'post',
    // data: diff,
    success: function(data) {
      $.featherlight(data);
      // console.log(data);
    }
  })
});

// Закрытие корневой папки
structure.on('close_node.jstree', function (e, data) {
  if (data.node.id === 'node_1') {
    $('#expand').toggleClass(['fa-expand', 'fa-compress']);
    $('#expand').attr('title', 'Развернуть дерево')
    // $('#expand').trigger('click');
  }
  // console.log(data.node);
});

$('#multiple-control').click(function(e){
  e.preventDefault();
  if ($(this).hasClass('fa-square-o')) {
    $(this).toggleClass(['fa-check-square-o', 'fa-square-o']);
    structure.jstree('show_checkboxes');
  } else {
    $(this).toggleClass(['fa-check-square-o', 'fa-square-o']);
    structure.jstree('hide_checkboxes');
  }
});

$('#expand').click(function(e){
  e.preventDefault();
  if ($(this).hasClass('fa-expand')) {
    $(this).toggleClass(['fa-expand', 'fa-compress']);
    structure.jstree('open_all');
     $(this).attr('title', 'Свернуть дерево')
  } else {
    $(this).toggleClass(['fa-expand', 'fa-compress']);
    structure.jstree('close_all');
    $(this).attr('title', 'Развернуть дерево')
  }
});


