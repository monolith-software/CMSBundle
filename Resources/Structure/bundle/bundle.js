import structureTpl from './views/structure.hbs';
import jstree from 'jstree';
import 'jstree/dist/themes/default/style.css';
import featherlight from 'featherlight';
import 'featherlight/src/featherlight.css';

let folders = {};
let structure = $('#structure');

// завернем создание дерева в function expression
let createStructure = function (dnd) {
  let promise = new Promise((resolve, reject) => {
    $.ajax({
      url: '/admin/system/megastructure/',
      type: 'get'
    })
      .done(data => {
        resolve(data);
      })
    // .error( e => {
    //   reject(e)
    // });
  });
  promise
    .then(onFulfilled => {
      // для рекусивного вывода дерева, приходящая в шаблон переменная
      // и св-ва объекта должны именоваться одинаково
      folders.folders = onFulfilled.structure;
      console.log('-----------------Дерево:', folders.folders);
      structure
        .append(structureTpl(folders))
        .jstree({
          'core': {
            'check_callback': function (op, node, par, pos, more) {
              // запрет на перемещение ноды в root
              if (more && more.dnd && (op === 'move_node' || op === 'copy_node') && par.parent === null) {
                return false;
              }
              // запрет на перемещение в модуль
              if (par.li_attr['data-node-type'] === 'module') {
                return false;
              }
              return true;
            },
            'multiple': false
          },
          'plugins': [
            dnd ? 'dnd' : '',
            'checkbox',
            // 'contextmenu'
          ],
          'checkbox': {
            'deselect_all': true,
            'three_state': false,
            'visible': false
          },
        });
        // выбор ноды
        structure.on('select_node.jstree', function (node, selected) {
          console.log('-----------------Выбранная нода: ', selected);
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
            success: function (data) {
              setTimeout(function () {
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
            error: function (data) {
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
      })
        // Закрытие корневой папки
        structure.on('close_node.jstree', function (e, data) {
        if (data.node.id === 'node_1') {
          $('#expand').toggleClass(['fa-expand', 'fa-compress']);
          $('#expand').attr('title', 'Развернуть дерево')
          // $('#expand').trigger('click');
        }
        // console.log(data.node);
        });

    })
    .catch(onRejected => {
      console.log(onRejected);
    });
};

createStructure();
// задестроим и построим заново дерево при изменении, событие эмитится из LayerController:index.html
document.addEventListener('change:structure', function (e) {
  structure.jstree('destroy');
  if ($('#dnd-true').hasClass('btn-danger')) {
    createStructure(true);
  } else {
    createStructure();
  }
});

$('#multiple-control').click(function (e) {
  e.preventDefault();
  if ($(this).hasClass('fa-square-o')) {
    $(this).toggleClass(['fa-check-square-o', 'fa-square-o']);
    structure.jstree('show_checkboxes');
  } else {
    $(this).toggleClass(['fa-check-square-o', 'fa-square-o']);
    structure.jstree('hide_checkboxes');
  }
});

$('#expand').click(function (e) {
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

// редактирование содержимого текстера
$(document).on('click', '.structure-node-edit-texter', function (e) {
  $('.loader').toggleClass('loader-active');
  e.preventDefault();
  $.featherlight({
    iframe: '/admin/Texter/' + $(this).attr('data-node-text-id') + '/?_overlay',
    iframeWidth: '100%',
    iframeHeight: '100%'
  });
  $('.loader').toggleClass('loader-active');
});

// эмуляция ссылки:( внутри ноды tree.js <a> не работает корректно
$(document).on('click', '.structure-node-folder-open', function (e) {
  window.open($(this).attr('data-folder-path'), '_blank');
});

// клик по шестеренке
$(document).on('click', '.structure-node-edit', function (e) {
  $('.loader').toggleClass('loader-active');
  let url = $(this).attr('data-node-type') == 'module'
    ? '/admin/system/structure/node/'
    : '/admin/system/structure/folder/'
  let id = $(this).attr('data-node-id')
  e.preventDefault();
  $.featherlight({
    iframe: url + id + '/?_overlay',
    iframeWidth: '100%',
    iframeHeight: '100%'
  });
  $('.loader').toggleClass('loader-active');
});

$('#create-folder').click(function (e) {
  $('.loader').toggleClass('loader-active');
  e.preventDefault();
  $.featherlight({
    iframe: '/admin/system/structure/folder/create/?_overlay',
    iframeWidth: '100%',
    iframeHeight: '100%'
  });
  $('.loader').toggleClass('loader-active');
});

$('#plug-module').click(function (e) {
  $('.loader').toggleClass('loader-active');
  e.preventDefault();
  $.featherlight({
    iframe: '/admin/system/structure/node/create/?_overlay',
    iframeWidth: '100%',
    iframeHeight: '100%'
  });
  $('.loader').toggleClass('loader-active');
});

$('#dnd-true').click(function(e) {
  $('.loader').toggleClass('loader-active');
  e.preventDefault();

  if ($(this).hasClass('btn-primary')) {
    $(this).toggleClass('btn-primary');
    $(this).toggleClass('btn-danger');
    structure.jstree('destroy');
    createStructure(true);
  } else {
    $(this).toggleClass('btn-primary');
    $(this).toggleClass('btn-danger');
    structure.jstree('destroy');
    createStructure();
  }
  $('.loader').toggleClass('loader-active');
});

