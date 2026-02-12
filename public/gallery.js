const galleries = document.querySelectorAll('.gallery');

galleries.forEach((gallery) => {
    const mainItem = gallery.querySelector('.main-img img');
    const items = gallery.querySelectorAll('.gallery-list-item');

    items.forEach((item) => {
        item.addEventListener('click', () => {
            mainItem.setAttribute('src', item.querySelector('img').dataset.fullSrc);
        })
    })
})