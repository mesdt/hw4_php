/* Lab 4 ajax */
$(document).ready(function(){
	$(".btn-ajax").click(function(){
		id = $(this).data("id"); 
		$.ajax({
				type: "DELETE",
				url: "/students",
				data: { id: id }
			})
			.done(function( data ) {
				$("#student-"+id).html( '<td colspan=3> <div class="alert alert-success" role="alert">'+data+'</div> </td>' );
			});		
	})
	$(".btn-grades").click(function(){
		id = $(this).data("id"); 
		$("tr").removeClass('alert-info');
		$.ajax({
				type: "GET",
				url: "/students/"+id+"/grades"
			})
			.done(function( data ) {
				
				$("#student-"+id).addClass('alert-info');
				$("#grades").html( "<h3>Оценки</h3>" );
				for (var subject of data.subjects) {
					$("#grades").append( subject.name+": "+ (data.scorez[subject.id] ? data.scorez[subject.id] : "-")+"<br>" );
				}
				
			});		
	})
	
})
