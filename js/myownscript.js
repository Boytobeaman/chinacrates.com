
function changeImage(id,newurl,oldurl){
    $("#"+id).hover(function () {
            $(this).attr("src",newurl);
        },
    function () {
            $(this).attr("src",oldurl);
        });
};

var siteUrl = "https://www.chinacrates.com";

changeImage("tochange2",siteUrl + "/wp-content/uploads/2016/08/plastic-egg-crate-15.png",siteUrl + "/wp-content/uploads/2016/07/600330.jpg")
changeImage("tochange3",siteUrl + "/wp-content/uploads/2016/08/stacking-crate-3628.png",siteUrl + "/wp-content/uploads/2016/07/euh360270.jpg")
changeImage("tochange4",siteUrl + "/wp-content/uploads/2016/08/attached-lid-container-37.png",siteUrl + "/wp-content/uploads/2016/07/nest6040335360270.jpg")
changeImage("tochange5",siteUrl + "/wp-content/uploads/2016/08/shelf-bin-ai.png",siteUrl + "/wp-content/uploads/2016/08/plast-shelf-bin-b3627.png")
changeImage("tochange6",siteUrl + "/wp-content/uploads/2016/08/nestingbox-6430a.png",siteUrl + "/wp-content/uploads/2016/07/nest64303627.jpg")
changeImage("tochange7",siteUrl + "/wp-content/uploads/2016/08/pallet-box-3628.png",siteUrl + "/wp-content/uploads/2016/07/1210foldable360270.jpg")
changeImage("tochange8",siteUrl + "/wp-content/uploads/2016/08/plastic-pallet-a.png",siteUrl + "/wp-content/uploads/2016/07/pallet3627.jpg")
changeImage("tochange9",siteUrl + "/wp-content/uploads/2016/08/plastic-coaming-box3627.png",siteUrl + "/wp-content/uploads/2016/08/plastic-boarding-crate3627.png")
changeImage("tochange10",siteUrl + "/wp-content/uploads/2017/02/household-storage-box-3627.jpg",siteUrl + "/wp-content/uploads/2017/02/household-plastic-box.jpg")
// �ı䱳��ͼƬ end
$(".product .summary").append('<div class="insertContact"><a href="mailto:seller006@joinplastic.com?subject=Inquiry about your plastic crate"><img src="' + siteUrl+'/img/homepage/message.gif">Contact Us&nbsp:seller006@joinplastic.com</a></div> ');
$(function () {

	$("#myCarouselHome p").hide();
	$("#myCarouselHome br").hide();
	$("#myCarouselHome").append(
		'<a class="left carousel-control" href="#myCarouselHome" role="button" data-slide="prev">' +
		'<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span><span class="sr-only">Previous</span>' +
		'</a>' +
		'<a class="right carousel-control" href="#myCarouselHome" role="button" data-slide="next">' +
		'<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span><span class="sr-only">Next</span>' +
		' </a>'
		);
	$("#myCarouselHome").prepend(
		'<ol class="carousel-indicators">' +
		'<li data-target="#myCarouselHome" data-slide-to="0" class="active"></li>' +
		'<li data-target="#myCarouselHome" data-slide-to="1"></li>' +
		'<li data-target="#myCarouselHome" data-slide-to="2"></li>' +
		'</ol>'
		);
})

