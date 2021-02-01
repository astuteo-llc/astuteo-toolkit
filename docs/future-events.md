## futureEvents

### What does this do?
It takes a query of entries that have a startDate and endDate date field and returns events that are still in the future.

### How to Use
```
{{ craft.astuteoToolkit.futureEvents({
        section: 'string', (required unless section is named events)
        limit: int, (optional)
        relatedTo: elementModel (optional, entry only so far)
    })
}}
```


Example:
```{% set futureEvents = craft.astuteoToolkit.futureEvents({section: 'events'}) %}```



[back to index](../README.md) 
