<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Report</title>
    <style>
        .main-container{
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-top: 20px;
        }

        div {
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 1em !important;
            color: black !important;
            font-family: Arial !important;
        }

        pre{
            font-size: 1em !important;
            color: black !important;
            font-family: Arial !important;
            margin-left: 20px;
        }

        .screenshot{
            height: 100%;
            width: 100%;            
        }
     
    </style>
</head>
<body>
    <div class="main-container">
    @isset($project)
        <div class="project"><b>Project:</b> {{ $project }}</div>
        @endisset

    @isset($module)
        <div class="module"><b>Module:</b> {{ $module }}</div>
        @endisset

    @isset($environment)
        <div class="environment"><b>Environment:</b> {{ $environment }}</div>
        @endisset

    @isset($title)
        <div class="title"><b>Title:</b> {{ $title }}</div>
        @endisset

    @isset($actualResult)
        <div class="actual-result"><b>Actual Result:</b> {{ $actualResult }}</div>
        @endisset

    @isset($description)
        <div class="description"><b>Description:</b> {{ $description }}</div>
        @endisset

    @isset($stepsToReproduce)
        <div class="steps-to-reproduce"><b>Steps to Reproduce:</b> <pre>{{ $stepsToReproduce }}</pre></div>
        @endisset

    @isset($expectedResult)
        <div class="expected-result"><b>Expected Result:</b> {{ $expectedResult }}</div>
        @endisset

    @isset($xpath)
        <div class="xpath"><b>Xpath:</b> {{ $xpath }}</div>
        @endisset

    @isset($screenshot)
        <div class="screenshot" style="background-image: url('{{$screenshot}}'); background-repeat: no-repeat;"></div>
        @endisset
    </div>
</body>
</html>