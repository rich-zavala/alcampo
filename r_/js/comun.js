//Configuración del ajax
$.ajaxSetup({
	dataType: 'json',
	cache: false
});

function c(s){ console.log(s); }

function stripDollarSign(s) {
	if (typeof s == 'string') { s = s.replace(/\$/g, ''); }
	return s;
}

function money(number) {

	var thousandsSeparator = ',';

	number = stripDollarSign(number);
	number = isNaN(number) || number == '' || number == null ? 0.00 : number;
	var numberStr = parseFloat(number).toFixed(2).toString();
	var numberFormatted = new Array(numberStr.slice(-3));   // this returns the decimal and cents
	numberStr = numberStr.substring(0, numberStr.length-3); // this removes the decimal and cents
	/*
	 * Why is there an `unshift()` function, but no `shift()`?
	 * Also, a `pop()` function would be handy here.
	 */
	while (numberStr.length > 3) {
			numberFormatted.unshift(numberStr.slice(-3)); // this prepends the last three digits to `numberFormatted`
			numberFormatted.unshift(thousandsSeparator); // this prepends the thousandsSeparator to `numberFormatted`
			numberStr = numberStr.substring(0, numberStr.length-3);  // this removes the last three digits
	}
	numberFormatted.unshift(numberStr); // there are less than three digits in numberStr, so prepend them

	return numberFormatted.join(''); // put it all together
}

function ForceNumericInput(This, AllowDot, AllowMinus)
{
	if(arguments.length == 1)
	{
				var s = This.value;
				// if "-" exists then it better be the 1st character
				var i = s.lastIndexOf("-");
				if(i == -1)
						return;
				if(i != 0)
						This.value = s.substring(0,i)+s.substring(i+1);
					return;
			}

			var code = event.keyCode;
			// c(code);
			switch(code)
			{
					case 8:     // backspace
					case 37:    // left arrow
					case 39:    // right arrow
					case 46:    // delete
					case 9:    // delete
					case 16:    // shift
					case 36:    // inicio
					case 35:    // fin
					event.returnValue=true;
					return;
			}
			if(code == 189)     // minus sign
			{
				if(AllowMinus == false)
				{
							event.returnValue=false;
							return;
					}


					// wait until the element has been updated to see if the minus is in the right spot
					var s = "ForceNumericInput(document.getElementById('"+This.id+"'))";
					setTimeout(s, 250);
					return;
			}

			if(AllowDot && (code == 190 || code == 110))
			{
				//alert($(This).val());
					if($(This).val().indexOf(".") >= 0)
					{
						// don't allow more than one dot
							event.returnValue=false;
							return;
					}
					event.returnValue=true;
					return;
			}
			// allow character of between 0 and 9
			if((code >= 48 && code <= 57) || (code >= 96 && code <= 105))
			{
					event.returnValue=true;
					return;
			}
			event.returnValue=false;
}

//Números
function dosDecimales(o)
{
	$(o).keydown(function(){ ForceNumericInput(this, true, false); });
}

//Sin decimales
function sinDecimal(o)
{
	$(o).keydown(function(){ ForceNumericInput(this, false, false); });
}

//Poner fecha
function fechacion()
{
	$('.fecha').datepicker({
		format: 'yyyy-mm-dd',
	}).on('changeDate', function(){
		$(this).datepicker('hide');
	}).blur(function(){
		if(!/^\d{4}-\d{2}-\d{2}$/.test($(this).val())) $(this).val(null);
	});
}

$().ready(function(){
	fechacion();

	//Sin enter
	$('.sinEnter :input').keypress(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });
	
	//Cambios de status y eliminaciones
	$('.listaAccion').each(function(){
		var t = $(this);
		var d = t.data();
		t.click(function(event){
			event.preventDefault();

			//Cambios de status
			if(t.hasClass('cambiar'))
			{
				var msg = (d.value != '-1') ? 'Confirme el cambio de status.' : 'Confirme la eliminación de este registro.\nEsta acción no podrá revertirse.'
				if(confirm(msg))
				{
					$.ajax({
						url: path + 'herramientas/cambios' + suffix,
						method: 'post',
						data: {
							controlador: d.controlador,
							table: d.table,
							key: d.key,
							id: d.id,
							value: d.value
						},
						success: function(data){
							if(data.error == 0)
							{
								var tr = t.parents('TR');
								if(d.value >= 0)
								{
									tr.find('.btnvigencia').hide();
									tr.find('.val' + d.value).show();
									t.parent().hide();
									tr.find('.cambiar').not(t).parent().show();
									
									//Quitar dropdown si es venta
									// console.log(d);
									if(d.table == 'ventas') tr.find('.dropdown-toggle').parent().fadeOut();
								}
								else
								{
									tr.fadeOut();
								}
							}
							else
							{
								alert('Ha ocurrido un error.\nLos cambios no fueron efectuados.');
							}
						},
						error: function()
						{
							alert('Existe un error en el servidor o en el inicio de sesión.\nPor favor, salga del sistema e intente nuevamente.');
						}
					});
				}
			}
		});
	});
});