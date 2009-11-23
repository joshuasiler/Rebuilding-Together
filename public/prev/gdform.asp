<%

Dim landing_page, host_url
Dim fso, outfile, filename, dirname, myFolder
Dim req_method, key, value
Dim bErr, errStr, bEmpty
On Error resume next
bErr = false
bEmpty = true
errStr = ""
Set fso = Server.CreateObject("Scripting.FileSystemObject")
host_url = Request.ServerVariables("HTTP_HOST")
req_method = Request.ServerVariables("REQUEST_METHOD")
dtNow = Now()
filename = Server.MapPath("\ssfm")
dirname = filename
filename = filename & "\gdform_" & DatePart("M", dtNow) & DatePart("D", dtNow) & DatePart("YYYY", dtNow) & DatePart("N", dtNow) & DatePart("S", dtNow)

Function FormatVariableLine(byval var_name, byVal var_value)
	Dim tmpStr
	tmpStr = tmpStr & "<GDFORM_VARIABLE NAME=" & var_name & " START>" & vbCRLF
	tmpStr = tmpStr & var_value & vbCRLF
	tmpStr = tmpStr & "<GDFORM_VARIABLE NAME=" & var_name & " END>"
	FormatVariableLine = tmpStr
end function

Sub OutputLine(byVal line)
   outfile.WriteLine(line)
end sub

if err.number = 0 then
	Set outfile = fso.CreateTextFile(filename, true, false)
	if err.number <> 0 then
			bErr = true
			errStr = "Error creating file! Directory may not be writable or may not exist.<br>Unable to process request."
	else
		if(req_method = "GET") then
			for each Item in request.QueryString
				if item <> "" then
					bEmpty = false
					key = item
					value = Request.QueryString(item)
					if(lcase(key) = "redirect") then
						landing_page = value
					else
						line = FormatVariableLine(key, value)
						Call OutputLine(line)
					end if
				end if	
			next
		elseif (req_method = "POST") then
			for each Item in request.form
				if item <> "" then
					bEmpty = false
					key = item
					value = Request.form(item)
					if(lcase(key) = "redirect") then
						landing_page = value
					else
						line = FormatVariableLine(key, value)
						Call OutputLine(line)
					end if
				end if	
			next
		end if
		outfile.close
	end if	
	if(bEmpty = true) AND errStr = "" then
		bErr = true
		errStr = errStr & "<br>No variables sent to form! Unable to process request."
	end if
	if(bErr = false) then	
		if (landing_page <> "") then
			response.Redirect "http://" & host_url & "/" & landing_page
		else
			response.Redirect "http://" & host_url	
		end if
	else
		Response.Write errStr
	end if	
	set fso = nothing
else
  Response.Write " An Error Occurred creating mail message. Unable to process form request at this time."
end if
%>
