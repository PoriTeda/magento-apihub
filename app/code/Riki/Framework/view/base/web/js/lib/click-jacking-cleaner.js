define(function () {
    return function (rawHtml) {
        return rawHtml.replace(/<!-- clickjacking[\s\S]*?clickjacking -->/, '');
    }
});