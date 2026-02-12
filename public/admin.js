/**
 * Sortable <ol data-sortable> with drag & drop.
 * After reordering, updates hidden inputs: name="sort[ID]" => value=index
 */
document.addEventListener('DOMContentLoaded', () => {
    const sortables = Array.from(document.querySelectorAll('[data-sortable]'));
    if (sortables.length === 0) return;

    sortables.forEach((list) => initSortableList(list));
});

function initSortableList(list) {
    let draggedItem = null;

    const items = list.querySelectorAll('li')


    list.addEventListener('dragstart', (e) => {
        const li = e.target.closest('li');
        if (!li || !list.contains(li)) return;

        draggedItem = li;
    });

    list.addEventListener('dragover', (e) => {
        if (!draggedItem) return;

        const overItem = e.target.closest('li');
        if (!overItem || overItem === draggedItem || !list.contains(overItem)) return;

        e.preventDefault(); // allow drop

        const rect = overItem.getBoundingClientRect();
        const insertBefore = e.clientY < rect.top + rect.height / 2;

        if (insertBefore) {
            list.insertBefore(draggedItem, overItem);
        } else {
            list.insertBefore(draggedItem, overItem.nextElementSibling);
        }
    });

    list.addEventListener('drop', (e) => {
        if (!draggedItem) return;
        e.preventDefault();

        draggedItem = null;
    });

    list.addEventListener('dragend', () => {
        if (!draggedItem) return;

        draggedItem = null;
    });
}